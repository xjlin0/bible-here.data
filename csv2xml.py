import csv
import sys
import xml.etree.ElementTree as ET

def csv_to_xml(csv_file, xml_file):
    root = ET.Element("XMLBIBLE", {
        "xmlns:xsi": "http://www.w3.org/2001/XMLSchema-instance",
        "xsi:noNamespaceSchemaLocation": "zef2005.xsd",
        "biblename": "Chinese Union Version",
        "status": "v",
        "version": "2.0.1.18",
        "type": "x-bible",
        "revision": "0"
    })

    books = {}

    with open(csv_file, newline='', encoding="utf-8") as f:
        reader = csv.DictReader(f)
        for row in reader:
            book = row["book"]
            chapter = row["chapter"]
            verse = row["verse"]
            text = row["text"]

            if book not in books:
                book_el = ET.SubElement(root, "BIBLEBOOK", {
                    "bnumber": book,
                    "bname": f"Book {book}",  
                    "bsname": f"B{book}"      
                })
                books[book] = {"element": book_el, "chapters": {}}

            book_el = books[book]["element"]

            if chapter not in books[book]["chapters"]:
                chapter_el = ET.SubElement(book_el, "CHAPTER", {"cnumber": chapter})
                books[book]["chapters"][chapter] = chapter_el

            chapter_el = books[book]["chapters"][chapter]

            # 建立 VERS
            ET.SubElement(chapter_el, "VERS", {"vnumber": verse}).text = text

    tree = ET.ElementTree(root)
    ET.indent(tree, space="  ", level=0)  # Python 3.9+
    tree.write(xml_file, encoding="utf-8", xml_declaration=True)

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python csv2xml.py filename.csv")
        sys.exit(1)

    csv_file = sys.argv[1]
    xml_file = csv_file.rsplit(".", 1)[0] + ".xml"

    csv_to_xml(csv_file, xml_file)
    print(f"Converted {csv_file} -> {xml_file}")

