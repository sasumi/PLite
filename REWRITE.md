# PLITE URL 重写规则

## 1. 介绍

路由重写包含两部分功能：
① 访问路由识别（如用户访问 `/user.html`，可以正确识别到 `controller/action `
② 生成路由重写规则（如代码中通过 `<?php echo url("user/index");?>` 可以生成 `/user.html` url字符串）

步骤①中访问路由识别由函数：`rewrite_resolve_path($mapping, $pathinfo);` 识别并自动设置成为当前路由（需要在路由生效代码前做这一步）。步骤②中生成重写规则URL由 `rewrite_bind_url($mapping)` 函数实现。通过绑定 `url($uri, $p)` 函数中事件 `EVENT_ROUTER_URL` 实现URL生成重写。两个步骤通过函数 `rewrite_setup($mapping)` 整合统一调用。

注意：一般CLI调用模式较少 `url()` 调用需求，如果需要在对应脚本中绑定URL生成规则，请手工调用 `rewrite_bind_url()`  函数。

## 2. 规则配置

配置规则为：
```php
[
   //URL匹配模式      uri模式（支持$1、￥2这种占位模式）,   数据key     数据值（支持$1、￥2这种占位模式）
   'url_pattern'  =>  ['uri_pattern',                   ['arg_key'=>'val_pattern']],
]
```

实际配置示例：
```php
<?php
$mapping = [
	# /course/detail_234234.html 课程详情
	'detail_{w}.html'      => ['course/detail', ['resource_id' => '$1']],

	# /course/tag1/tag2.html 二级标签
	'catalog/{w}/{w}.html' => ['course/catalog', ['t1' => '$1', 't2' => '$2']],

	# /course/tag1.html 一级标签
	'catalog/{w}.html'     => ['course/catalog', ['t1' => '$1']],

	# /course/p_234234.html 课程详情
	'search.html'   => ['course/search'],

	'{w}.html' => ['page/$1'],

	'{w}/{w}.html' => ['$1/$2', ['pp' => '$1']],
]

```

## 3. 启动(httpd 模式)
请在 public/index.php `start_web()` 函数调用前加入以下代码：
```php
//启动重写：
router_setup_rewrite_mapping($mapping);
start_web();
```

## 4. 启动(CLI模式)

请在脚本开始执行逻辑前加入以下代码：

```php
//绑定url重写
rewrite_bind_url($mapping);
```

