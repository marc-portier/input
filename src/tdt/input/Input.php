<?php

/**
 * Input is the main model class
 * @author Pieter Colpaert
 * @author Miel Vander Sande
 */

namespace tdt\input;

class Input {

    //Extractor, Mapper, Loader
    private $e, $m, $l;
    //$ts is an array of transformers, as more transformations can be 
    private $ts;

    /**
     * Reads the input.ini file and initiates all classes according to their configuration
     */
    public function __construct($config) {

        /*$validator = new JsonSchema\Validator();
        
        $validator->check(json_decode(json_encode($config),false), json_decode($schema));
        if (!$validator->isValid()) {
            throw new TDTException("551",$validator->getErrors());
            }*/
        // create an instance of the right extract class
        $extractmethod = $config["extract"];
        $extractorclass = "tdt\\input\\extract\\" . $extractmethod;
        $this->e = new $extractorclass($config);

        //transformers
        $this->ts = array();

        // mapper
        if(!empty($config["map"])){
            $mapmethod = "tdt\\input\\map\\" . $config["map"];
            $this->m = new $mapmethod($config);
        }

        // loader
        if(!empty($config["load"])){
            $loadclass = "tdt\\input\\load\\" . $config["load"];
            $this->l = new $loadclass($config);
        }
    }
    

    /**
     * Execute our model according to the configuration parsed in the constructor
     */
    public function execute() {

        $start = microtime(true);
        $numberofchunks = 0;
        
        echo 'Started ETML process';

        while ($this->e->hasNext()) {                    
            //1. EXTRACT
            $chunk = $this->e->pop();

            //2. TRANSFORM
            foreach ($this->ts as $t) {
                $chunk = $t->execute($chunk);
            }

            //3. MAP
            if (!empty($this->m)) {
                $chunk = $this->m->execute($chunk);
            }

            //4. LOAD
            if (!empty($this->l)) {
                $this->l->execute($chunk);
            }

            $numberofchunks++;
        }

        $duration = microtime(true) - $start;
        $msg = "Loaded $numberofchunks chunks in the store in " . $duration . "s. \n";
        echo $msg;
    }

}
