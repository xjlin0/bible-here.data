# Bible Here WordPress Plugin - 開發者指南

## 概述

Bible Here 是一個功能完整的WordPress聖經插件，支持多語言聖經閱讀、搜索、比對和自動經文識別功能。本插件遵循WordPress Plugin Boilerplate標準結構開發。

## 系統要求

### 最低要求

* **WordPress**: 5.0 或更高版本

* **PHP**: 7.4 或更高版本

* **MySQL**: 5.5.3 或更高版本 / **MariaDB**: 10.0.5 或更高版本

* **數據庫字符集**: utf8mb4 (支持多語言字符)

### 推薦配置

* **MySQL**: 5.7.6 或更高版本 (支持ngram全文搜索)

* **MariaDB**: 10.0.15 或更高版本 (支持Mroonga引擎)

* **PHP Memory Limit**: 256MB 或更高

* **Max Execution Time**: 300秒 (用於大型聖經文件導入)

## 數據庫配置要求

### 1. UTF8MB4 支持

確保MySQL/MariaDB支持utf8mb4字符集以處理多語言內容：

```sql
-- 檢查字符集支持
SHOW CHARACTER SET LIKE 'utf8mb4';

-- 設置數據庫字符集
ALTER DATABASE your_database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. 全文搜索配置

#### MySQL 5.7.6+ 配置

為了支持中文等CJK語言的全文搜索，需要配置ngram解析器：

```sql
-- 在my.cnf中添加以下配置
[mysqld]
ngram_token_size = 2
ft_min_word_len = 1
innodb_ft_min_token_size = 1
```

重啟MySQL服務後，創建全文索引：

```sql
CREATE FULLTEXT INDEX idx_verse_fulltext ON wp_bible_here_en_kjv(verse) WITH PARSER ngram;
```

#### MariaDB 10.0.15+ 配置

安裝並啟用Mroonga存儲引擎以支持全文搜索：

```sql
-- 安裝Mroonga插件
INSTALL PLUGIN Mroonga SONAME 'ha_mroonga.so';

-- 創建支持全文搜索的表
CREATE TABLE wp_bible_here_search (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content TEXT,
    FULLTEXT(content)
) ENGINE=Mroonga DEFAULT CHARSET=utf8mb4;
```

### 3. InnoDB 引擎要求

所有數據表必須使用InnoDB存儲引擎以支持：

* 事務處理

* 外鍵約束

* 全文搜索 (MySQL 5.6+)

* 更好的併發性能

## 項目結構

本插件遵循WordPress Plugin Boilerplate標準結構：

```
bible-here/
├── admin/                          # 管理後台相關文件
│   ├── class-bible-here-admin.php  # 管理後台主類
│   ├── css/                        # 管理後台樣式
│   ├── js/                         # 管理後台JavaScript
│   └── partials/                   # 管理後台模板
├── includes/                       # 核心功能類
│   ├── class-bible-here.php        # 主插件類
│   ├── class-bible-here-loader.php # 鉤子載入器
│   ├── class-bible-here-i18n.php   # 國際化
│   ├── class-bible-here-activator.php   # 插件啟用
│   └── class-bible-here-deactivator.php # 插件停用
├── public/                         # 前端相關文件
│   ├── class-bible-here-public.php # 前端主類
│   ├── css/                        # 前端樣式
│   ├── js/                         # 前端JavaScript
│   └── partials/                   # 前端模板
├── languages/                      # 語言文件
├── assets/                         # 靜態資源和數據文件
│   ├── data/                       # 聖經數據文件
│   │   ├── kjv.sql                 # KJV聖經數據
│   │   ├── cross-references.sql    # 串珠數據
│   │   └── strong-numbers.sql      # Strong Number數據
│   └── xml/                        # Zefania XML文件
├── bible-here.php                  # 主插件文件
├── uninstall.php                   # 卸載腳本
├── README.txt                      # WordPress.org README
└── README.md                       # 開發者README
```

## 安裝和啟動

### 1. 開發環境設置

```bash
# 克隆項目到WordPress插件目錄
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/your-repo/bible-here.git

# 進入插件目錄
cd bible-here

# 安裝依賴 (如果使用Composer)
composer install

# 設置文件權限
chmod -R 755 .
chmod -R 644 *.php
```

### 2. 數據庫初始化

插件啟用時會自動創建必要的數據表。如需手動初始化：

```bash
# 導入基礎數據結構
mysql -u username -p database_name < assets/data/schema.sql

# 導入KJV聖經數據
mysql -u username -p database_name < assets/data/kjv.sql

# 導入串珠數據
mysql -u username -p database_name < assets/data/cross-references.sql
```

### 3. WordPress插件啟用

1. 登入WordPress管理後台
2. 進入「插件」→「已安裝插件」
3. 找到「Bible Here」並點擊「啟用」
4. 啟用後會自動創建數據表並導入基礎數據

## 開發指南

### 1. 添加新的聖經版本

```php
// 在includes/class-bible-here-importer.php中添加
public function import_bible_version($xml_url, $version_info) {
    // 下載Zefania XML文件
    $xml_content = wp_remote_get($xml_url);
    
    // 解析XML並導入數據庫
    $this->parse_zefania_xml($xml_content, $version_info);
    
    // 創建對應的數據表
    $this->create_version_table($version_info['table_name']);
}
```

### 2. 自定義經文識別正則表達式

```javascript
// 在public/js/bible-here-public.js中修改
const BIBLE_REFERENCE_REGEX = {
    // 英文縮寫模式
    en: /\b(Gen|Exo|Lev|Num|Deu)\s*(\d+):(\d+)(?:-(\d+))?\b/gi,
    // 中文縮寫模式
    zh: /\b(創|出|利|民|申)\s*(\d+):(\d+)(?:-(\d+))?/gi
};
```

### 3. 搜索功能架構

#### 搜索系統組件

**後端搜索服務** (`includes/class-bible-here-bible-service.php`)
```php
// 核心搜索方法
public function search_verses($search_text, $options = []) {
    // 查詢優化
    $optimized_query = $this->optimize_search_query($search_text);
    
    // 緩存檢查
    $cache_key = $this->generate_cache_key($optimized_query, $options);
    $cached_result = get_transient($cache_key);
    
    if ($cached_result !== false) {
        return $cached_result;
    }
    
    // 執行搜索
    $results = $this->execute_search($optimized_query, $options);
    
    // 結果預處理
    $processed_results = $this->preprocess_search_results($results, $search_text);
    
    // 緩存結果
    set_transient($cache_key, $processed_results, 3600);
    
    return $processed_results;
}
```

**搜索優化算法**
```php
// 查詢優化
private function optimize_search_query($search_text) {
    // 移除多餘空格
    $search_text = preg_replace('/\s+/', ' ', trim($search_text));
    
    // 移除停用詞
    $stop_words = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
    $words = explode(' ', $search_text);
    $filtered_words = array_diff($words, $stop_words);
    
    return implode(' ', $filtered_words);
}

// 相關性評分
private function calculate_relevance_score($verse, $search_terms) {
    $score = 0;
    $verse_lower = strtolower($verse);
    
    foreach ($search_terms as $term) {
        $term_lower = strtolower($term);
        // 完全匹配得分更高
        $exact_matches = substr_count($verse_lower, $term_lower);
        $score += $exact_matches * 10;
        
        // 詞根匹配
        if (strpos($verse_lower, $term_lower) !== false) {
            $score += 5;
        }
    }
    
    return $score;
}
```

#### 前端搜索組件

**搜索界面** (`public/js/bible-here-public.js`)
```javascript
// 搜索建議功能
function initSearchSuggestions() {
    let searchTimeout;
    
    $('#bible-search-input').on('input', function() {
        const query = $(this).val();
        
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            if (query.length >= 2) {
                fetchSearchSuggestions(query);
            }
        }, 300);
    });
}

// AJAX搜索請求
function performSearch(query, options = {}) {
    const searchData = {
        action: 'bible_here_search',
        nonce: bible_here_ajax.nonce,
        search_text: query,
        version: options.version || 'kjv',
        books: options.books || [],
        search_mode: options.search_mode || 'fulltext',
        page: options.page || 1,
        per_page: options.per_page || 20
    };
    
    return $.ajax({
        url: bible_here_ajax.ajax_url,
        type: 'POST',
        data: searchData,
        beforeSend: function() {
            showSearchLoading();
        }
    }).done(function(response) {
        if (response.success) {
            displaySearchResults(response.data);
            updateSearchHistory(query);
        }
    }).fail(function() {
        showSearchError();
    }).always(function() {
        hideSearchLoading();
    });
}
```

### 4. 添加新的API端點

```php
// 在includes/class-bible-here-ajax.php中添加
public function handle_custom_api() {
    // 驗證nonce
    if (!wp_verify_nonce($_POST['nonce'], 'bible_here_nonce')) {
        wp_die('Security check failed');
    }
    
    // 處理API邏輯
    $result = $this->process_request($_POST);
    
    // 返回JSON響應
    wp_send_json_success($result);
}
```

## 維護指南

### 1. 數據庫維護

```sql
-- 定期優化數據表
OPTIMIZE TABLE wp_bible_here_en_kjv;

-- 檢查全文索引狀態
SHOW INDEX FROM wp_bible_here_en_kjv WHERE Key_name = 'idx_verse_fulltext';

-- 重建全文索引（如果需要）
DROP INDEX idx_verse_fulltext ON wp_bible_here_en_kjv;
CREATE FULLTEXT INDEX idx_verse_fulltext ON wp_bible_here_en_kjv(verse);
```

### 2. 搜索性能優化

**緩存策略**
```php
// 搜索結果緩存
class Bible_Here_Search_Cache {
    private $cache_prefix = 'bible_here_search_';
    private $cache_expiry = 3600; // 1小時
    
    public function get_search_cache($query, $options) {
        $cache_key = $this->generate_cache_key($query, $options);
        return get_transient($cache_key);
    }
    
    public function set_search_cache($query, $options, $results) {
        $cache_key = $this->generate_cache_key($query, $options);
        set_transient($cache_key, $results, $this->cache_expiry);
    }
    
    public function clear_search_cache() {
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_{$this->cache_prefix}%' 
             OR option_name LIKE '_transient_timeout_{$this->cache_prefix}%'"
        );
    }
}
```

**數據庫查詢優化**
```php
// 使用預處理語句和索引優化
public function search_verses_optimized($search_text, $options = []) {
    global $wpdb;
    
    // 構建優化的SQL查詢
    $sql = $wpdb->prepare(
        "SELECT v.*, 
                MATCH(v.verse) AGAINST(%s IN NATURAL LANGUAGE MODE) as relevance_score,
                CONCAT(b.name, ' ', v.chapter_number, ':', v.verse_number) as reference
         FROM {$wpdb->prefix}bible_here_en_kjv v
         JOIN {$wpdb->prefix}bible_here_books b ON v.book_number = b.book_number
         WHERE MATCH(v.verse) AGAINST(%s IN NATURAL LANGUAGE MODE)
         ORDER BY relevance_score DESC, v.book_number, v.chapter_number, v.verse_number
         LIMIT %d OFFSET %d",
        $search_text,
        $search_text,
        $options['per_page'] ?? 20,
        ($options['page'] ?? 1 - 1) * ($options['per_page'] ?? 20)
    );
    
    return $wpdb->get_results($sql);
}
```

**性能監控**
```php
// 搜索性能統計
class Bible_Here_Search_Stats {
    public function log_search_performance($query, $execution_time, $result_count) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'Bible Here Search: Query="%s", Time=%.4fs, Results=%d',
                $query,
                $execution_time,
                $result_count
            ));
        }
        
        // 記錄到數據庫統計表
        $this->save_search_stats([
            'query' => $query,
            'execution_time' => $execution_time,
            'result_count' => $result_count,
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_client_ip()
        ]);
    }
    
    public function get_performance_report() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT 
                AVG(execution_time) as avg_time,
                MAX(execution_time) as max_time,
                COUNT(*) as total_searches,
                AVG(result_count) as avg_results
             FROM {$wpdb->prefix}bible_here_search_stats 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
    }
}
```

### 3. 日誌記錄

```php
// 使用WordPress內建日誌功能
if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
    error_log('Bible Here: ' . $message);
}
```

## 故障排除

### 常見問題

1. **全文搜索不工作**

   * 檢查MySQL版本和ngram配置
   * 確認全文索引已正確創建
   * 驗證搜索關鍵字長度（至少2個字符）
   * 檢查InnoDB引擎配置

   ```sql
   -- 檢查全文索引狀態
   SHOW INDEX FROM wp_bible_here_en_kjv WHERE Key_name LIKE '%fulltext%';
   
   -- 重建全文索引
   ALTER TABLE wp_bible_here_en_kjv DROP INDEX idx_verse_fulltext;
   ALTER TABLE wp_bible_here_en_kjv ADD FULLTEXT(verse) WITH PARSER ngram;
   ```

2. **搜索性能緩慢**

   * 啟用查詢緩存和結果緩存
   * 優化數據庫索引結構
   * 限制搜索結果數量
   * 使用分頁載入

   ```php
   // 性能調試
   define('BIBLE_HERE_DEBUG_SEARCH', true);
   
   // 在搜索方法中添加
   $start_time = microtime(true);
   // ... 搜索邏輯 ...
   $execution_time = microtime(true) - $start_time;
   
   if (defined('BIBLE_HERE_DEBUG_SEARCH') && BIBLE_HERE_DEBUG_SEARCH) {
       error_log("Search took: {$execution_time}s for query: {$search_text}");
   }
   ```

3. **搜索建議不顯示**

   * 檢查JavaScript控制台錯誤
   * 確認AJAX端點正確註冊
   * 驗證nonce安全檢查
   * 檢查搜索歷史數據

   ```javascript
   // 調試搜索建議
   console.log('Search suggestions request:', {
       query: searchQuery,
       action: 'bible_here_search_suggestions',
       nonce: bible_here_ajax.nonce
   });
   ```

4. **搜索結果高亮不正確**

   * 檢查正則表達式轉義
   * 確認HTML標籤不被破壞
   * 驗證多語言字符支持

   ```php
   // 安全的高亮實現
   private function highlight_search_terms($text, $terms) {
       foreach ($terms as $term) {
           $escaped_term = preg_quote($term, '/');
           $text = preg_replace(
               '/(' . $escaped_term . ')/iu',
               '<mark class="search-highlight">$1</mark>',
               $text
           );
       }
       return $text;
   }
   ```

5. **中文字符顯示亂碼**

   * 確認數據庫使用utf8mb4字符集
   * 檢查WordPress wp-config.php中的DB\_CHARSET設置
   * 驗證HTTP響應頭字符編碼

6. **搜索緩存問題**

   * 緩存過期時間設置
   * 緩存鍵衝突檢查
   * 手動清除緩存機制

   ```php
   // 清除搜索緩存的WP-CLI命令
   if (defined('WP_CLI') && WP_CLI) {
       WP_CLI::add_command('bible-here clear-search-cache', function() {
           $cache = new Bible_Here_Search_Cache();
           $cache->clear_search_cache();
           WP_CLI::success('Search cache cleared successfully.');
       });
   }
   ```

7. **導入大型聖經文件超時**

   * 增加PHP max\_execution\_time
   * 使用分批導入機制
   * 考慮使用WP-CLI命令行導入

### 調試模式

```php
// 在wp-config.php中啟用調試
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// 插件特定調試
define('BIBLE_HERE_DEBUG', true);
```

## 貢獻指南

1. Fork本項目
2. 創建功能分支 (`git checkout -b feature/new-feature`)
3. 提交更改 (`git commit -am 'Add new feature'`)
4. 推送到分支 (`git push origin feature/new-feature`)
5. 創建Pull Request

## 許可證

本插件使用GPL v2或更高版本許可證。詳見LICENSE文件。

## 支持

如有問題或建議，請：

1. 查看[常見問題](FAQ.md)
2. 提交[Issue](https://github.com/your-repo/bible-here/issues)
3. 參與[討論](https://github.com/your-repo/bible-here/discussions)

