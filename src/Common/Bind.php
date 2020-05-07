<?php
namespace MDword\Common;

use MDword\WordProcessor;

class Bind
{
    private $data;
    /**
     * @var WordProcessor
     */
    private $wordProcessor;
    
    private $pre = '';
    
    private $binds = [];
    
    public function __construct($wordProcessor,$data,$pre='') {
        $this->wordProcessor = $wordProcessor;
        $this->data = $data;
        $this->pre = $pre;
    }
    
    public function bindValue($name,$keyList,$pBindName=null,$callback=null,$emptyCallBack=null) {
        static $binds = [];
        
        //loop
        if(!is_null($pBindName) && isset($binds[$pBindName])) {
//             if($name == 'link2') {
//                 var_dump($binds[$pBindName]);exit;
//             }
            foreach($binds[$pBindName] as $bind) {
                $bind->bindValue($name,$keyList,null,$callback,$emptyCallBack);
            }
            
            return $this;
        }
        
        $data = $this->data;
        foreach($keyList as $key) {
            $data = $data[$key];
        }
        
        if(is_array($data)) {
            $count = count($data);
            $this->wordProcessor->clone($name.$this->pre,$count);
            $i = 0;
            foreach($data as $subData) {
                $binds[$name][] = new Bind($this->wordProcessor, $subData, $this->pre.'#'.$i++);
            }
            
            if($count === 0 && !is_null($emptyCallBack)) {
                $this->wordProcessor->cloneTo($emptyCallBack($data,$this->data),$name.$this->pre,$count);
            }
        }else{
            if(!is_null($callback)) {
                $data = $callback($data,$this->data);
            }
            $this->wordProcessor->setValue($name.$this->pre,$data);
        }
        
        return $this;
    }
    
    public function bindSubValue() {
        
        return $this;
    }
}