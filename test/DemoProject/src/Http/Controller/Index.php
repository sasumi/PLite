<?php
namespace Demo\Project\Http\Controller;

use Demo\Project\Http\AuthorizeFreeTrait;
use Demo\Project\Http\Controller;

class Index extends Controller {
	use AuthorizeFreeTrait;
}
