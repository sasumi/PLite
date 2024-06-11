# PLITE URL 重写规则

## 规则配置DEMO
```php
$mapping = [
	# /course/detail_234234.html 课程详情
	'detail_{W}.html'      => ['course/detail', ['resource_id' => '$1']],

	# /course/tag1/tag2.html 二级标签
	'catalog/{W}/{W}.html' => ['course/catalog', ['t1' => '$1', 't2' => '$2']],

	# /course/tag1.html 一级标签
	'catalog/{W}.html'     => ['course/catalog', ['t1' => '$1']],

	# /course/p_234234.html 课程详情
	'search.html'   => ['course/search'],

	'{W}.html' => ['page/$1'],

	'{W}/{W}.html' => ['$1/$2', ['pp' => '$1']],
]

```

## 启动
请在 public/index.php `start_web()` 函数调用前加入以下代码：
```php
//启动重写：
router_setup_rewrite_mapping($mapping);
start_web();
```

