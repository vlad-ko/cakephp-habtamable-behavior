<?php
 /**
 * Save any two HABTM related models at the same time... with existing record check
 * i.e. Loaction HABTM Address, will save both at once. (if a given address does not exist 
 * in the addresses table, it will save address and relation, otherwise we'll get existing Address.id and save relation)
 * Use saveAll() to ensure transactional support. Remember that your DB should support transactions.
 * If you don't care (?) about transactions, save() works just as well.
 * 
 * Search accross HABTM Models, without modifying your find:
 * $this->Location->find('all', array('conditions' => array('Address.city' => 'Miami')));
 */
  class HabtamableBehavior extends ModelBehavior {
   
 /**
 * No need to check these fields, as they are pretty much always unique
 * These will really depend on your app, I've included some common ones for mine
 */
   private $fieldsToSkip = array('id', 'created', 'modified', 'updated', 'lat', 'lng', 'is_active', 'pid');
 
 /**
 * Figure out what models we are working with.
 * ... and set relevant properties.
 */    
   public function setup(&$Model, $settings = array()) {       
    if (!isset($this->settings[$Model->alias])) {
      $this->settings[$Model->alias] = array('joinType' => 'INNER', 'fieldsToSkip' => $this->fieldsToSkip);      
    }
   
    if(!isset($this->settings[$Model->alias]['habtmModel'])) {      
      $this->settings[$Model->alias]['habtmModel'] = key($Model->hasAndBelongsToMany);  
    }
      
    $this->settings[$Model->alias] = Set::merge($this->settings[$Model->alias], $settings);   
    
  }
  
/**
 * Ability to "search" accross HABTM models *  
 */
  public function beforeFind(&$Model, $query) {
    if($this->checkHabtmConditions($Model, $query)) {
      $this->changeBind($Model);    
    }
    
    return TRUE;  
  }

/**
 * We only need to change the bind if the HABTM model is present
 * somewhere in the conditions.
 * To make array keys easier to search for a match, we flatten
 * and implode the keys it into a string.
 */
 private function checkHabtmConditions($Model, $query) {
  if (empty($query['conditions'])) {
    $query['conditions'] = array();
  }
  $searchableConditions = implode('.', Set::flatten(array_keys($query['conditions'])));
  
  return (bool) strpos($searchableConditions, $this->settings[$Model->alias]['habtmModel']); 
 }
 
/**  
 * Fake model bindings and construct a JOIN
 */ 
  private function changeBind(&$Model) {
       
    $Model->bindModel(array('hasOne' => array($Model->hasAndBelongsToMany[$this->settings[$Model->alias]['habtmModel']]['with'] => array(
                                                'foreignKey' => FALSE,
                                                'type' => $this->settings[$Model->alias]['joinType'],
                                                'conditions' => array($Model->hasAndBelongsToMany[$this->settings[$Model->alias]['habtmModel']]['with'] . '.' .
                                                                      $Model->hasAndBelongsToMany[$this->settings[$Model->alias]['habtmModel']]['associationForeignKey'] . ' = ' . 
                                                                      $Model->alias . '.' . $Model->primaryKey)),
                                              $this->settings[$Model->alias]['habtmModel'] => array(
                                                'foreignKey' => FALSE,
                                                'type' => $this->settings[$Model->alias]['joinType'],
                                                'conditions' => array(
                                                  $this->settings[$Model->alias]['habtmModel'] . '.' . $Model->{$this->settings[$Model->alias]['habtmModel']}->primaryKey . ' = ' . $Model->hasAndBelongsToMany[$this->settings[$Model->alias]['habtmModel']]['with'] . '.' .
                                                  $Model->hasAndBelongsToMany[$this->settings[$Model->alias]['habtmModel']]['foreignKey']                                                  
                                                )))));  
  }
  
/**
 * A little hack to simulate validation of both models at once
 * (possibly needs re-thinking)
 */  
  public function beforeValidate(&$Model) {
    if(!$this->doValidation($Model)) {
      $Model->invalidate(NULL);
    }
  }
 
 /**
  * If related HABTM model fails validation we invalidate
  * our other
  */ 
  private function doValidation(&$Model) {
    $Model->{$this->settings[$Model->alias]['habtmModel']}->set($Model->data[$this->settings[$Model->alias]['habtmModel']]);
    
    if($Model->{$this->settings[$Model->alias]['habtmModel']}->validates()) {
      return TRUE;
    }
    
    return FALSE;
  }  
 /**
 * If we have an existing record matching our input data, then all we need is the record ID.
 */    
   public function beforeSave(&$Model) {       
    $existingId = $this->getExistingId($Model);
        
    if(!empty($existingId)) {
      $Model->data[$this->settings[$Model->alias]['habtmModel']][$this->settings[$Model->alias]['habtmModel']] = $existingId;
    }
            
    return TRUE;
  }  
 /**
 * Either save record and get new ID.
 * ... or grab existing ID.
 */ 
  private function getExistingId(&$Model) {
   $conditions = $this->buildCondition($Model);
   
   $existingRecord = $Model->{$this->settings[$Model->alias]['habtmModel']}->find('first', array('conditions' => $conditions,
                                                                      'recursive' => -1));  
   
   if(!$existingRecord) {
     $Model->{$this->settings[$Model->alias]['habtmModel']}->create();
     $Model->{$this->settings[$Model->alias]['habtmModel']}->save($Model->data[$this->settings[$Model->alias]['habtmModel']]);
     
     $existingId = $Model->{$this->settings[$Model->alias]['habtmModel']}->id;
   } else {     
     $existingId = $existingRecord[$this->settings[$Model->alias]['habtmModel']][ClassRegistry::init($this->settings[$Model->alias]['habtmModel'])->primaryKey];
   }
   
   return $existingId;
  }
 
 /**
 * Building proper conditions to search our HABTM model for an existing record
 */  
  private function buildCondition(&$Model) { 
     
    foreach($Model->{$this->settings[$Model->alias]['habtmModel']}->schema() as $field => $meta) {
      if(!in_array($field, $this->settings[$Model->alias]['fieldsToSkip'])) {
        if(isset($Model->data[$this->settings[$Model->alias]['habtmModel']][$field]) && !empty($Model->data[$this->settings[$Model->alias]['habtmModel']][$field])) {                   
          $conditions[] = array($Model->{$this->settings[$Model->alias]['habtmModel']}->alias . '.' . $field => $Model->data[$this->settings[$Model->alias]['habtmModel']][$field]);  
        }
      }  
    } 
       
    return $conditions;
  } 
      
}
?>