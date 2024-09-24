<?php

namespace Oro\Bundle\MailChimpBundle\Util;

/**
 * This is replacement of CallbackFilterIterator to fix bug (https://bugs.php.net/bug.php?id=72051)
 */
class CallbackFilterIteratorCompatible extends \FilterIterator
{
    /**
     * @var callable $callback
     */
    protected $callback;

    /**
     * @var mixed $current
     */
    protected $current;

    /**
     * CallbackFilterIterator constructor.
     */
    public function __construct(\Iterator $iterator, callable $callback)
    {
        $this->callback = $callback;
        parent::__construct($iterator);
    }

    #[\Override]
    public function accept(): bool
    {
        $iterator = $this->getInnerIterator();
        $this->current = $iterator->current();
        return call_user_func_array($this->callback, array(&$this->current, $iterator->key(), $iterator));
    }

    #[\Override]
    public function current(): mixed
    {
        if (!$this->getInnerIterator()->valid()) {
            return null;
        }

        if ($this->accept()) {
            return $this->current;
        }

        $this->getInnerIterator()->next();

        return $this->current();
    }
}
