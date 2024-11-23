# PLite lightweight functional framework

lightweight, high-performance PHP development framework is implemented using functional methods . The framework integrates routing, controllers, configuration, static resource version control, reference event monitoring and triggering, and other capabilities to quickly implement a simple and lightweight web system. The framework uses namespace + constant prefix to protect the runtime from polluting other code bases and ensure code compatibility.

## 1. Installation

### 1.1 Manual Installation

Please clone the plité repository via git to download the latest version of the framework code:

```shell
git clone https://github.com/sasumi/PLite.git
```

Copy the code to the project code directory and import the startup file:

```php
include_once "autoload.php";
```

### 1.2 Installation via `composer`

Framework environment dependencies:
a. PHP version ≥ 7.1
b. php ext-json extension
c. lfphp/func function (automatically installed)

Install using Composer:

```shell
composer require lfphp/plite
```

### 1.3 Installation via `lfphp/pls`

`lfphp/pls` is the plité installation script. The script includes multiple functions such as plité installation, project initialization, ORM generation, CRUD code generation, etc. It is recommended to use it.

Installation pls:
```shell
composer require lfphp/pls
```

## 2. Basic usage

For basic usage of the framework, please refer to the code examples in the `test/DemoProject` directory.

### 2.1 Configuration File

**Global Control Variables**

Except for `PLITE_APP_ROOT`, which needs to be manually configured in the project, other `PLITE_*` constants have default values. The following lists only some important constants. If you need to know all constant definitions, you can refer to the framework code file: `src/defines.php`.

| Variable Name | Description | Default Value |
| -------------------------- | --------------------------------------------------------------- | ----------------------------------------------- |
| `PLITE_APP_ROOT` | The project running root directory, other configuration file path logic is generally based on this directory extension. | Required|
| `PLITE_SITE_ROOT` | Site access URL path, such as: http://www.site.com/, of course, can be simplified to `/` absolute configuration. | `/` |
| `PLITE_CONFIG_PATH` | Configuration file directory, provided for use by the `get_config()` function. To distinguish it from other PHP files, it is generally named in the file.inc.php format. The configuration file uses the return syntax to return the configuration value. | `PLITE_APP_ROOT.'/config'` |
| `PLITE_PAGE_PATH` | Template page directory, used by the `include_page()` function. | `PLITE_APP_ROOT.'/src/page'` |
| `PLITE_PAGE_NO_FOUND` | 404 page template | `404.php` (placed under `PLITE_PAGE_PATH`) |
| `PLITE_PAGE_ERROR` | 500 Site Error Page | `5xx.php` (placed under `PLITE_PAGE_PATH`) |

**Program Configuration**

The framework's default configuration file directory is: `PLITE_APP_ROOT + '/config'`, which can be reset by `CONFIG_PATH`.
The configuration file in this directory is named in the format of `config_key.inc.php`. The data returned by the configuration file can be obtained through the function `get_config('config_key')`, or the internal array sub-items can be directly obtained through the `get_config('parent/child')` method.
Example:

```php
//The content of the configuration file site.inc.php is:
<?php
return [
	'name'=>'Site 1',
'admin' => [
'user'=>'jack',
'email'=>'jack@email.com'
]
];

//Get the configuration method:
//1. Get the site name:
$site_name = get_config('site/name');

//2. Get the site administrator's email address
$admin_email = get_config('site/admin/email');
```

The framework requires the following configuration files by default:

1. `routes.inc.php` provides the website access routing table
2. `static_version.inc.php` static resource version configuration information (always used in `include_js` and other functions)

### 2.2 Routing System
#### 2.2.1 Routing Configuration
The default parameter name in the URL is: `r` (can be reset by `PLITE_ROUTER_KEY`), and the default route is passed in queryString. For example: www.abc.com/?r=user/info&id=1.
The framework's routing configuration defaults to: `routes.inc.php` (can be reset via `PLITE_ROUTER_CONFIG_FILE`).
The route configuration syntax is:

```php
return [
//Mode ① URI matching => class name + '@' + method name
'' => IndexController::class.'@index',
'user/create' => UserController::class.'@create',
        
//Mode ② contains wildcard URI string => class name + '@' + method name, or wildcard
'product/*' => UserController::class.'@*',
]
```
#### 2.2.2 Routing System Usage
```php

//?r=user/create
echo url('user/create'); //Generate a URL route to create a user

//<input name="r" value="user/update"/>
//<input name="id" value="1"/>
echo url_input('user/update', ['id'=>1]); //Generate an HTML input string for updating user 1 information
```

#### 2.2.3 Route Rewriting

Please refer to the [routing rewrite rules description](REWRITE.md) document.

### 2.3 Controller

There are no restrictions on framework controllers. Any class or method can be registered as a controller. Of course, it is recommended to design a project controller parent class during use to facilitate the processing of some unified behaviors (such as authentication, unified logging, etc.). The `$_REQUEST` parameter in the router will be passed as the first parameter to the `action` method.

```php
//Recommend Controller model

/**
* Controller is a common parent class defined by the public, which is convenient for implementing unified logic processing in the Controller::__construct() method
*/
class Order extends Controller {
use AuthorizedTrait; //It is recommended to create traits to implement capabilities such as authentication
    
/**
* @param array $request //The framework routing mechanism uniformly passes the $_REQUEST variable to the action method
*/
public function index($request){
self::noVisited();
var_dump($request['hello']);
}
    
/**
* Static methods will not be accessed by routing
*/
public static function noVisited(){
        
}
}
```

### 2.4 Views

The framework supports the `include_page` function to introduce PHP template files.
The page directory defaults to: `APP_ROOT+/src/page` and can be reset by `PLITE_PAGE_PATH`.
Directions:

```php
include_page('user/info.php', ['id'=>1]); //Introduce the src/page/user/info.php file and pass the parameters to the file.
```

## 3. Framework runtime events

custom processing of key events by registering events `event_register($event, $payload)`. The framework supports the following events:

| Event key | Event description | Callback function parameter description |
| -------------------------- | -------- | ------------- |
| EVENT_APP_START | The web program starts running (environment variables have been configured) | None |
| EVENT_APP_BEFORE_EXEC | Before controller execution | Parameter 1: hit controller (short name)<br />Parameter 2: hit action (case insensitive) |
| EVENT_APP_EXECUTED | controller execution completed | Parameter 1: Execution result return parameter<br />Parameter 2: Hit controller (abbreviation)<br />Parameter 3: Hit action (case insensitive) |
| EVENT_APP_FINISHED | The web application has finished running | None |
| EVENT_APP_EXCEPTION | web program running abnormally | Parameter 1: Exception object<br />Parameter 2: hit controller (abbreviation)<br />Parameter 3: hit action (case insensitive) |
| EVENT_ROUTER_HIT | Route hit (web framework can execute code directly in route configuration without controller) | Parameter 1: hit route item (string, closure function, wildcard) |
| EVENT_ROUTER_URL | `url()` route generation function execution | Parameter 1: generate url<br /> Parameter 2: input URI<br /> Parameter 3: input parameter array |
| EVENT_BEFORE_INCLUDE_PAGE | `include_page()` function execution starts (the default C/A execution result in web mode will include the page) | Parameter 1: relative path of template file<br />Parameter 2: input parameter array |
| EVENT_AFTER_INCLUDE_PAGE | `include_page()` function execution ends (the default C/A execution result in web mode will include the page) | Parameter 1: template file absolute path<br />Parameter 2: input parameter array |

## 4. Copyright Notice

The framework adopts the `MIT` copyright statement. Please abide by the copyright statement during use.

## 5. Others

The framework is only a lightweight routing framework. It is recommended to use it with the following frameworks when necessary:
① `PORM` PHP ORM library (`lfphp/porm`)
② `Logger` PHP log library (`lfphp/logger`)
③ `Cache` PHP cache library (`lfphp/cache`)
