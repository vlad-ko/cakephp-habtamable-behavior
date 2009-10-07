<?php
 /**
 * Save any two HABTM related models at the same time... with existing record check
 * i.e. Loaction HABTM Address, will save both at once. (if a given address does not exist 
 * in the addresses table, it will save address and relation, otherwise we'll get existing Address.id and save relation)
 * 
 * Search accross HABTM Models, without modifying your find:
 * $this->Location->find('all', array('conditions' => array('Address.city' => 'Miami')));
 */
  class HabtamableBehavior extends ModelBehavior {
   
 /**
 * No need to check these fields, as they are pretty much always unique
 */
   private $fieldsToSkip = array('id', 'created', 'modified', 'updated');
   
 /**
 * Related model. Using example above, it would be Address
 */   
   private $habtmModel;

 /**
 * Hold conditions being passed to the find()
 */  
  private $conditions;
  
 /**
 * Figure out what models we are working with.
 * ... and set relevant class properties.
 */    
   public function setup(&$Model, $settings = array()) { 
    if(empty($settings)) {    
      $this->settings[$Model->alias]['habtmModel'] = key($Model->hasAndBelongsToMany);
    }
    
    $this->habtmModel = $this->settings[$Model->alias]['habtmModel'];   
  }
  
/**
 * Ability to "search" accross HABTM models *  
 */
  public function beforeFind(&$Model) {
    $this->rewriteFind($Model);    
    return TRUE;  
  }
 
/**  
 * Fake model bindings and construct a join
 */
 
  private function rewriteFind(&$Model) {
        
    $Model->bindModel(array('hasOne' => array($Model->hasAndBelongsToMany[$this->habtmModel]['with'] => array(
                                                'foreignKey' => FALSE,
                                                'type' => 'INNER',
                                                'conditions' => array($Model->hasAndBelongsToMany[$this->habtmModel]['with'] . '.' .
                                                                      $Model->hasAndBelongsToMany[$this->habtmModel]['associationForeignKey'] . ' = ' . 
                                                                      $Model->alias . '.' .$Model->primaryKey)),
                                              $this->habtmModel => array(
                                                'foreignKey' => FALSE,
                                                'type' => 'INNER',
                                                'conditions' => array(
                                                  $this->habtmModel . '.' . $Model->{$this->habtmModel}->primaryKey . ' = ' . $Model->hasAndBelongsToMany[$this->habtmModel]['with'] . '.' .
                                                  $Model->hasAndBelongsToMany[$this->habtmModel]['foreignKey']                                                  
                                                )))));  
  }
  
  private function extractConditions() {
    
  }
  /**
 * If we have an existing record matching our input data, then all we need is the record ID.
 */    
   public function beforeSave(&$Model) {
                       
      $existingId = $this->getExistingId($Model);
      
      if(!empty($existingId)) {
        $Model->data[$this->habtmModel][$this->habtmModel] = $existingId;
      }
            
    return TRUE;
  }
  
 /**
 * Either save record and get new ID.
 * ... or grab existing ID.
 */ 
  private function getExistingId(&$Model) {
   $conditions = $this->buildCondition($Model);
   
   $existingRecord = $Model->{$this->habtmModel}->find('first', array('conditions' => $conditions,
                                                                      'recursive' => -1));  
    
   if(!$existingRecord) {
     $Model->{$this->habtmModel}->create();
     $Model->{$this->habtmModel}->save($Model->data[$this->habtmModel]);
     
     $existingId = $Model->{$this->habtmModel}->id;
   } else {     
     $existingId = $existingRecord[$this->habtmModel][ClassRegistry::init($this->habtmModel)->primaryKey];
   }
   
   return $existingId;
  }
 
  /**
 * Building proper conditions to search our HABTM model for an existing record
 */  
  private function buildCondition(&$Model) {    
   
    foreach($Model->{$this->habtmModel}->schema() as $field => $type) {      
      if(!in_array($field, $this->fieldsToSkip)) {
        if(isset($Model->data[$this->habtmModel][$field])) {          
          $conditions[] = array($Model->{$this->habtmModel}->name . '.' . $field => $Model->data[$this->habtmModel][$field]);  
        } else {
          return FALSE;
        }
      }  
    }      
    return $conditions;
  } 
      
}
?>