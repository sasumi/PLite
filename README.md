# PLite 轻量函数式框架

采用函数式实现轻量化、高性能PHP开发框架。框架集成路由、控制器、配置、静态资源版本控制、引用式事件监听及触发等能力，可快速实现一个简单轻量的web系统。框架采用命名空间+常量前缀方式保护运行时不污染其他代码库，保证代码实现兼容性。

## 安装

使用Composer进行安装：

```
composer require lfphp/plite
```

### 基本用法

框架基本用法请参考 `test/proj` 目录中的代码示例。

### 配置文件

框架默认配置文件目录为：`PLITE_APP_ROOT + '/config'`，可通过 `CONFIG_PATH` 重置
该目录下配置文件命名格式为 `config_key.inc.php` ，通过函数 `get_config('config_key')` 方式获取该配置文件return回的数据，或通过 `get_config('parent/child')` 方式直接获取到内部数组子项。
举例：

```php
//配置文件 site.inc.php 内容为：
<?php
return [
	'name'=>'站点1',
    'admin' => [
        'user'=>'jack',
        'email'=>'jack@email.com'
    ]
];

//获取配置方式为：
//1、获取站点名称：
$site_name = get_config('site/name');

//2、获取站点管理员邮箱
$admin_email = get_config('site/admin/email');
```

### 路由系统

URL中默认参数名称为： `r` (可通过 `PLITE_ROUTER_KEY` 重置)。
框架路由配置默认为：`routes.inc.php` (可以通过 `PLITE_ROUTER_CONFIG_FILE` 重置)。
路由配置语法为：

```php
return [
    //模式① URI匹配 => 类名+'@'+方法名称
    '' => IndexController::class.'@index',
    'user/create' => UserController::class.'@create',
        
    //模式② 包含通配符 URI字符串 => 类名+'@'+方法名称，或通配符
    'product/*' => UserController::class.'@*',
]
```

### 控制器

框架控制器无任何限制，任何类、方法都可以注册成为控制器，使用过程建议新建项目控制器父类，方便对一些统一行为（如鉴权、统一日志等）进行处理。

### 视图

框架支持 `include_page` 函数引入php模板文件。
页面目录默认为：`APP_ROOT+/src/page` 可通过 `PLITE_PAGE_PATH` 重置。
使用方法：

```php
include_page('user/info.php', ['id'=>1]); //标识引入 src/page/user/info.php 文件，同时传递参数到文件内部。
```


## 版权声明

框架采用 `MIT` 版权声明，请在使用过程遵守该版权声明。

## 其他

框架仅为轻量路由框架，建议在有需求情况，配合以下框架使用：
① `PORM`` PHP ORM库（`lfphp/porm`）
② `Logger` PHP 日志库 （`lfphp/logger`）
③ `Cache` PHP 缓存库 (`lfphp/cache`)
