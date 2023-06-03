<?php
namespace Demo\Project\Http;

trait AuthorizeFreeTrait {
	public function onAuthorize(){
		//check auth state
		return true;
	}
}
