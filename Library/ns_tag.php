<?php
class ns_tag {
    protected $params;
    protected $rawparams;
    protected $content;
    function __construct($rawparams,$contents) {
    $this->rawparams = $rawparams;
    $this->params = $this->remove_quote($rawparams);
        $this->contents = $contents;
    }
    /**
     * Remove Quotes on the side of Params
     * @param array $rawparams 
     */
    function remove_quote($rawparams){
        foreach($rawparams as &$value){
            $quote = substr($value,0,1);
            if(($quote=='"' || $quote=="'") && $quote==substr($value,-1)){
                $value = substr($value,1,-1);
            }
        }
        return $rawparams;
    }
}
