import csv
import sys
import os
import re
import xml.etree.ElementTree as ET

# ---- helpers ----
def norm_key(s: str) -> str:
    # 規格化欄位名：小寫 + 移除非英數
    return re.sub(r'[^a-z0-9]+', '', s.lower())

def resolve_columns(fieldnames):
    """根據實際表頭自動對應欄位名稱，支援多種常見命名。"""
    idx = {norm_key(fn): fn for fn in fieldnames}

    def pick(candidates, required=True):
        for cand in candidates:
            k = norm_key(cand)
            if k in idx:
                return idx[k]
        if required:
            raise KeyError(f"Missing required column. Tried: {candidates}")
        return None

    cols = {}
    # 必要欄位
    cols["book_number"] = pick(["Book Number", "book"])
    cols["chapter"]     = pick(["Chapter", "chapter"])
    cols["verse"]       = pick(["Verse", "verse"])
    cols["text"]        = pick(["Text", "text"])
    # 可選欄位（書卷中文名/英文名）
    cols["book_name"]   = pick(["Book Name", "book_name", "bname"], required=False)
    return cols

def pretty_indent(tree: ET.ElementTree):
    # Python 3.9+ 內建縮排；較舊版本用後備
    try:
        ET.indent(tree, space="  ", level=0)  # type: ignore
    except Exception:
        def _indent(elem, level=0):
            i = "\n" + level*"  "
            if len(elem):
                if not elem.text or not elem.text.strip():
                    elem.text = i + "  "
                for child in elem:
                    _indent(child, level+1)
                if not elem.tail or not elem.tail.strip():
                    elem.tail = i
            else:
                if level and (not elem.tail or not elem.tail.strip()):
                    elem.tail = i
        _indent(tree.getroot())

# ---- transform ----
PAREN_STRONG_RE = re.compile(r"\{\((H|G)(\d+)\)\}")  # {(H1234)} -> {H1234}

def normalize_strongs(text: str) -> str:
    return PAREN_STRONG_RE.sub(r"{\1\2}", text)

def csv_to_xml(csv_file: str, xml_file: str):
    biblename = os.path.splitext(os.path.basename(csv_file))[0]

    root = ET.Element(
        "XMLBIBLE",
        {
            "xmlns:xsi": "http://www.w3.org/2001/XMLSchema-instance",
            "xsi:noNamespaceSchemaLocation": "zef2005.xsd",
            "biblename": biblename,
            "status": "v",
            "version": "2.0.1.18",
            "type": "x-bible",
            "revision": "0",
        },
    )

    books_cache = {}  # book_number -> {"elem": BIBLEBOOK, "chapters": {chapter: CHAPTER}}

    with open(csv_file, newline='', encoding="utf-8") as f:
        reader = csv.DictReader(f)
        cols = resolve_columns(reader.fieldnames or [])

        for row in reader:
            book_number = str(row[cols["book_number"]]).strip()
            chapter     = str(row[cols["chapter"]]).strip()
            verse       = str(row[cols["verse"]]).strip()
            text        = str(row[cols["text"]])

            # 正規化 Strong’s：{(H1234)} -> {H1234}；其他如 {H7225} 原樣保留
            text = normalize_strongs(text)

            # 取得書卷名稱（若無則用預設）
            book_name = (
                str(row[cols["book_name"]]).strip()
                if cols["book_name"] and row.get(cols["book_name"]) not in (None, "")
                else f"Book {book_number}"
            )

            # 建立/取得 BIBLEBOOK
            if book_number not in books_cache:
                book_el = ET.SubElement(
                    root, "BIBLEBOOK",
                    {"bnumber": book_number, "bname": book_name, "bsname": f"B{book_number}"}
                )
                books_cache[book_number] = {"elem": book_el, "chapters": {}}

            book_el = books_cache[book_number]["elem"]

            # 建立/取得 CHAPTER
            chapters = books_cache[book_number]["chapters"]
            if chapter not in chapters:
                chapters[chapter] = ET.SubElement(book_el, "CHAPTER", {"cnumber": chapter})
            chapter_el = chapters[chapter]

            # 新增 VERS
            ET.SubElement(chapter_el, "VERS", {"vnumber": verse}).text = text

    tree = ET.ElementTree(root)
    pretty_indent(tree)
    tree.write(xml_file, encoding="utf-8", xml_declaration=True)

# ---- CLI ----
if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python csv2xml.py <filename.csv>")
        sys.exit(1)

    csv_path = sys.argv[1]
    xml_path = os.path.splitext(csv_path)[0] + ".xml"
    csv_to_xml(csv_path, xml_path)
    print(f"Converted {csv_path} -> {xml_path}")
