# PLite 轻量函数式框架

采用函数式实现轻量化、高性能PHP开发框架。框架集成路由、控制器、配置、静态资源版本控制、引用式事件监听及触发等能力，可快速实现一个简单轻量的web系统。框架采用命名空间+常量前缀方式保护运行时不污染其他代码库，保证代码实现兼容性。

## 安装

使用Composer进行安装：

```shell
composer require lfphp/plite
```

### 基本用法

框架基本用法请参考 `test/DemoProject` 目录中的代码示例。

### 配置文件

#### 一、全局控制变量

除 `PLITE_APP_ROOT` 需要在项目中手工配置，其他 `PLITE_*` 常量均有缺省值。以下仅列出部分重要常量，如需了解全部常量定义，可以查阅框架代码文件：`src/defines.php` 。

| 变量名称              | 说明                                                         | 缺省值                                    |
| --------------------- | ------------------------------------------------------------ | ----------------------------------------- |
| `PLITE_APP_ROOT`      | 项目运行根目录，其他相当配置文件路径逻辑一般基于该目录延展。 | 必填                                      |
| `PLITE_SITE_ROOT`     | 站点访问URL路径，如：http://www.site.com/，当然可以简化为 `/` 绝对配置。 | `/`                                       |
| `PLITE_CONFIG_PATH`   | 配置文件目录，提供给 `get_config()` 函数使用。为与其他php文件区分，一般采用 file.inc.php 格式命名。配置文件内部采用 return 语法返回配置值。 | ` PLITE_APP_ROOT.'/config'`               |
| `PLITE_PAGE_PATH`     | 模板页面目录，提供给 `include_page()` 函数使用。             | `PLITE_APP_ROOT.'/src/page'`              |
| `PLITE_PAGE_NO_FOUND` | 404 页面模板                                                 | `404.php` （放置在 `PLITE_PAGE_PATH` 下） |
| `PLITE_PAGE_ERROR`    | 500 网站错误页面                                             | `5xx.php` （放置在 `PLITE_PAGE_PATH` 下） |

框架默认配置文件目录为：`PLITE_APP_ROOT + '/config'`，可通过 `CONFIG_PATH` 重置。
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

框架默认需要以下配置文件：

1. `routes.inc.php` 提供网站访问路由表
2. `static_version.inc.php` 静态资源版本配置信息（在 `include_js` 等函数总使用）

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

框架控制器无任何限制，任何类、方法都可以注册成为控制器。当然，使用过程建议设计项目控制器父类，方便对一些统一行为（如鉴权、统一日志等）进行处理。

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
