<?php
namespace MDword\Edit\Part;

use MDword\Common\PartBase;
use MDword\Common\Build;

class Rels extends PartBase
{
    public $partInfo = null;
    
    protected $relationshipTypes =
    //--RELATIONSHIPTYPES--array (
  0 => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/chart',
  1 => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings',
  2 => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image',
  3 => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme',
  4 => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles',
  5 => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/numbering',
  6 => 'http://schemas.microsoft.com/office/2011/relationships/commentsExtended',
  7 => 'http://schemas.microsoft.com/office/2011/relationships/people',
  8 => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/comments',
  9 => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/fontTable',
  10 => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/webSettings',
  11 => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/endnotes',
  12 => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/customXml',
  13 => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/footnotes',
  14 => 'http://schemas.microsoft.com/office/2016/09/relationships/commentsIds',
  15 => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/package',
  16 => 'http://schemas.microsoft.com/office/2011/relationships/chartColorStyle',
  17 => 'http://schemas.microsoft.com/office/2011/relationships/chartStyle',
  18 => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer',
  19 => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/header',
)//--RELATIONSHIPTYPES--
    ;
    
    public function __construct($word,\DOMDocument $DOMDocument) {
        parent::__construct($word);
        $this->DOMDocument = $DOMDocument;
        
        if(MDWORD_DEBUG) {
            $Relationships = $this->DOMDocument->getElementsByTagName('Relationship');
            foreach ($Relationships as $Relationship) {
                if(!in_array($type = $this->getAttr($Relationship, 'Type'), $this->relationshipTypes)) {
                    $this->relationshipTypes[] = $type;
                }
            }
            $build = new Build();
            $build->replace('RELATIONSHIPTYPES', $this->relationshipTypes, __FILE__);
        }
    }
    
    public function replace($rid,$file) {
        $Relationships = $this->DOMDocument->getElementsByTagName('Relationship');
        $length = $Relationships->length;
        foreach ($Relationships as $Relationship) {
            if($Relationship->getAttribute('Id') === $rid) {
                $type = $this->getAttr($Relationship, 'Type');
                switch ($type) {
                    case $this->relationshipTypes[2]:
                        $target = 'media/image'.++$length.'.png';
                        break;
                }
                
                //删除旧文件
                $oldValue = $this->partInfo['dirname'].'/'.$Relationship->getAttribute('Target');
                $this->word->zip->deleteName($oldValue);
                
                //替换
                $Relationship->setAttribute('Target',$target);
                $target = $this->partInfo['dirname'].'/'.$target;
                $this->word->zip->addFromString($target, file_get_contents($file));
            }
        }
    }
    
    public function insert($file,$fileType) {
        static $rIdMax = null;
        if(is_null($rIdMax)) {
            $rIdMax = 1;
            $Relationships = $this->DOMDocument->getElementsByTagName('Relationship');
            foreach ($Relationships as $Relationship) {
                $id = intval(str_replace('rId', '', $Relationship->getAttribute('Id')));
                if($id > $rIdMax) {
                    $rIdMax = $id;
                }
            }
        }
        
        $rIdMax = $rIdMax + 1;
        
        switch ($fileType) {
            case MDWORD_IMG:
                $type = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image';
                $imageInfo = @getimagesize($file);
                if($imageInfo === false) {
                    return false;
                }
                
                $mimeArr = explode('/', $imageInfo['mime'],2);
                $target = 'media/image'.$rIdMax.'.'.$mimeArr[1];
                $rId = 'rId'.$rIdMax;
                $mixed = [
                    'Relationship'=>[
                        'Id'=>$rId,
                        'Type'=>$type,
                        'Target'=>$target,
                    ],
                ];
                $Relationship = $this->creatNode($mixed);
                $this->DOMDocument->getElementsByTagName('Relationships')->item(0)->appendChild($Relationship);
                
                $target = $this->partInfo['dirname'].'/'.$target;
                $this->word->zip->addFromString($target, file_get_contents($file));
                return ['rId'=>$rId,'imageInfo'=>$imageInfo];
                break;
        }
    }
    
    public function getTarget($rid=null) {
        $Relationships = $this->DOMDocument->getElementsByTagName('Relationship');
        foreach ($Relationships as $Relationship) {
            if(is_null($rid)) {
                return $this->partInfo['dirname'].'/'.$Relationship->getAttribute('Target');
            }
            
            if($Relationship->getAttribute('Id') === $rid) {
                return $this->partInfo['dirname'].'/'.$Relationship->getAttribute('Target');
            }
        }
        
        return null;
    }

    public function setNewChartRels($chartCount){
        $Relationships = $this->DOMDocument->getElementsByTagName('Relationship');
        $tempMaxId = 0;
        $chartNum = 0;
        foreach ($Relationships as $Relationship) {
            $target = $Relationship->getAttribute('Target');
            if(strpos($target,'charts') !== false){
                $chartNum++;
               // $Relationship->parentNode->removeChild($Relationship);
            }
            $rId = $Relationship->getAttribute('Id');
            preg_match('/(\d+)/',$rId,$match);
            if($match[1] > $tempMaxId){
                $tempMaxId = $match[1];
            }
        }
        $Relationships2 = $this->DOMDocument->getElementsByTagName('Relationship');
        $chartRid = [];
        for($i = 0 ; $i < $chartCount ; $i++){
            $chartNum++;
            $tempMaxId++;
            $copy = clone $Relationships[0];
            $copy->setAttribute('Id','rId'.$tempMaxId);
            $chartRid[] = $tempMaxId;
            $copy->setAttribute('Type',$this->relationshipTypes[0]);
            $copy->setAttribute('Target','charts/chart'.$chartNum.'.xml');
            $Relationships[0]->parentNode->appendChild($copy);
        }
        return $chartRid;
    }
}
