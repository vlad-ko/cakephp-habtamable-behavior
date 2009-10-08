> ### Habtamable behavior (!!! Still alpha, quite a few to-do's !!!)
>
> **Save two HABTM models at once**
>
> For example you are saving a Location (my sweet penthouse) with all the relevant info
> and the Address (the actual address of the penthouse).
>
> Example: $this->Location->saveAll($this->data);
> In the above example Location and Address will be saved with transactional support.
> (FYI save() works just as well.)
>
> **Search accross HABTM models**
>
> For example, you can do something like:
> $this->Location->find('all', array('conditions' => array('Location.is_active' => 1, 'Address.city' => 'Miami')));
> 
> (The searching works by "faking" a hasOne bind, and therefore building a join to specify conditions.
> For the time being only INNER join is supported and the bind is not permament.)
> 
> Of couse, Location HABTM Address
> 
> Features (so far):
> 
> - If address (HABTM record) exists will use existing ID
> - Will ensure proper relationship saving
> - Model validation 
> - Allows you to search across HABTM models
> 
> Very simple to use:
> 
> Using our example above, in the Location model, all you need to do is to add:   
> *public $actsAs = array('Habtamable');*
> 
> The above presumes that in the Location model definition you have:  
> *public $hasAndBelongsToMany = array('Address');*
> 
> The rest is taken care of for you.
> 
> Suggestions, improvments, etc. are needed.
> [Contact here](http://wp.me/peDIi-cZ)
