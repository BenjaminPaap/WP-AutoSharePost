<?php

class Template
{
    
    public $extension = 'phtml';
    
    public function render($script)
    {
        $script = WP_AUTOSHAREPOST_DIR . '/tpl/' . $script . '.' . $this->extension;
        
        if (is_readable($script)) {
            include($script);
        } else {
            throw new Exception('Template script not found "' . $script . '"');
        }
    }
    
    public function assign($name, $value)
    {
        $this->$name = $value;
    }
    
    public function __set($name, $value)
    {
        $this->assign($name, $value);
    }
    
}