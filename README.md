# PLite 轻量函数式框架

采用函数式实现轻量化、高性能PHP开发框架。框架集成路由、控制器、配置、静态资源版本控制、引用式事件监听及触发等能力，可快速实现一个简单轻量的web系统。框架采用命名空间+常量前缀方式保护运行时不污染其他代码库，保证代码实现兼容性。

## 1. 安装

### 1.1 手工安装

请通过git克隆plite仓库下载最新版本框架代码：

```shell
git clone https://github.com/sasumi/PLite.git
```

复制代码到项目代码目录，引入启动文件：

```php
include_once "autoload.php";
```

### 1.2 通过`composer`安装

框架环境依赖：
a. php版本≥7.1
b. php ext-json 扩展
c. lfphp/func 函数（自动安装）

使用Composer进行安装：

```shell
composer require lfphp/plite
```

### 1.3 通过`lfphp/pls`安装

`lfphp/pls` 为plite安装脚本。脚本包含安装plite、初始化项目、ORM生成、CRUD代码生成等多项功能，推荐使用。

安装pls：
```shell
composer require lfphp/pls
```

## 2. 基本用法

框架基本用法请参考 `test/DemoProject` 目录中的代码示例。

### 2.1 配置文件

**全局控制变量**

除 `PLITE_APP_ROOT` 需要在项目中手工配置，其他 `PLITE_*` 常量均有缺省值。以下仅列出部分重要常量，如需了解全部常量定义，可以查阅框架代码文件：`src/defines.php` 。

| 变量名称              | 说明                                                         | 缺省值                                    |
| --------------------- | ------------------------------------------------------------ | ----------------------------------------- |
| `PLITE_APP_ROOT`      | 项目运行根目录，其他相当配置文件路径逻辑一般基于该目录延展。 | 必填                                      |
| `PLITE_SITE_ROOT`     | 站点访问URL路径，如：http://www.site.com/，当然可以简化为 `/` 绝对配置。 | `/`                                       |
| `PLITE_CONFIG_PATH`   | 配置文件目录，提供给 `get_config()` 函数使用。为与其他php文件区分，一般采用 file.inc.php 格式命名。配置文件内部采用 return 语法返回配置值。 | ` PLITE_APP_ROOT.'/config'`               |
| `PLITE_PAGE_PATH`     | 模板页面目录，提供给 `include_page()` 函数使用。             | `PLITE_APP_ROOT.'/src/page'`              |
| `PLITE_PAGE_NO_FOUND` | 404 页面模板                                                 | `404.php` （放置在 `PLITE_PAGE_PATH` 下） |
| `PLITE_PAGE_ERROR`    | 500 网站错误页面                                             | `5xx.php` （放置在 `PLITE_PAGE_PATH` 下） |

**程序配置**

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

### 2.2 路由系统
#### 2.2.1 路由配置
URL中默认参数名称为： `r` (可通过 `PLITE_ROUTER_KEY` 重置），缺省路由通过queryString方式传参。如：www.abc.com/?r=user/info&id=1。
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
#### 2.2.2 路由系统使用
```php 

//?r=user/create
echo url('user/create'); //生成一条创建用户的URL路由

//<input name="r" value="user/update"/>
//<input name="id" value="1"/>
echo url_input('user/update', ['id'=>1]); //生成一串用于更新用户1信息的html input字符串
```

#### 2.2.3 路由重写

请参考 [路由重写规则说明](REWRITE.md) 文档。

### 2.3 控制器

框架控制器无任何限制，任何类、方法都可以注册成为控制器。当然，使用过程建议设计项目控制器父类，方便对一些统一行为（如鉴权、统一日志等）进行处理。路由器中 `$_REQUEST` 参数会被当成第一个参数传递给 `action` 方法。

```php
//推荐 Controller 模型

/**
 * Controller 为公共定义的共同父类，方便在 Controller::__construct() 方法中实现统一逻辑处理
 */
class Order extends Controller {
    use AuthorizedTrait; //推荐建立 trait 来实现类似鉴权等能力
    
    /**
     * @param array $request //框架路由机制统一传递 $_REQUEST 变量到 action方法中
     */
    public function index($request){
        self::noVisited();
        var_dump($request['hello']);
    }
    
    /**
     * 静态方法不会被路由访问到
     */
    public static function noVisited(){
        
    }    
}
```

### 2.4 视图

框架支持 `include_page` 函数引入php模板文件。
页面目录默认为：`APP_ROOT+/src/page` 可通过 `PLITE_PAGE_PATH` 重置。
使用方法：

```php
include_page('user/info.php', ['id'=>1]); //标识引入 src/page/user/info.php 文件，同时传递参数到文件内部。
```

## 3. 框架运行时事件

框架运行过程支持通过注册事件 `register_event($event, $payload)` 实现对关键事件进行自定义处理。框架支持事件列表有：

| 事件key               | 事件描述 | 回调函数参数说明 |
| --------------------- | -------- | ---------------- |
| EVENT_APP_START    | web程序运行开始（环境变量已经配置完成） | 无 |
| EVENT_APP_BEFORE_EXEC    | controller执行前 | 参数1：命中控制器（简称）<br />参数2：命中动作（不区分大小写） |
| EVENT_APP_EXECUTED    | controller执行完成 | 参数1：执行结果返回参数<br />参数2：命中控制器（简称）<br />参数3：命中动作（不区分大小写） |
| EVENT_APP_FINISHED    | web程序运行结束 | 无 |
| EVENT_APP_EXCEPTION    | web程序运行异常 | 参数1：异常 Exception 对象<br />参数2：命中控制器（简称）<br />参数3：命中动作（不区分大小写） |
| EVENT_ROUTER_HIT    | 路由命中（web框架可以直接在路由配置中执行代码，无需controller） | 参数1：命中路由项目（字符串、闭包函数、通配符） |
| EVENT_ROUTER_URL    | `url()` 路由生成函数执行 | 参数1：生成url<br />参数2：输入URI<br />参数3：输入参数数组 |
| EVENT_BEFORE_INCLUDE_PAGE    | `include_page()` 函数执行开始（web模式默认C/A 执行结果会包含页面） | 参数1：模板文件相对路径<br />参数2：输入参数数组 |
| EVENT_AFTER_INCLUDE_PAGE    | `include_page()` 函数执行结束（web模式默认C/A 执行结果会包含页面） | 参数1：模板文件绝对路径<br />参数2：输入参数数组 |

## 4. 版权声明

框架采用 `MIT` 版权声明，请在使用过程遵守该版权声明。

## 5. 其他

框架仅为轻量路由框架，建议在有需求情况，配合以下框架使用：
① `PORM` PHP ORM库（`lfphp/porm`）
② `Logger` PHP 日志库 （`lfphp/logger`）
③ `Cache` PHP 缓存库 (`lfphp/cache`)
