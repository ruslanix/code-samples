QueueOrmListener is a Doctrine events prePersist/preUpdate listener.

There is an entity:

class Queue
{
    protected $price;
    protected $priceCustom;
    …..
}

$priceCustom - is some type of discount and calculated from the $price field.
So, when $price updated, we should automatically update $priceCustom.
This is done  by QueueOrmListener.


