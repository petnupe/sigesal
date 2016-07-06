<?php
/**
 * Lancamento Active Record
 * @author  <your-name-here>
 */
class Lancamento extends TRecord
{
    const TABLENAME = 'public.lancamento';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
   
    private $system_user;
   
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('data_lancamento');
        parent::addAttribute('valor');
        parent::addAttribute('descricao');
        parent::addAttribute('cliente_id');
        $this->Cliente = new SystemUser($this->cliente_id);
    }
    
    public function set_system_user(SystemUser $object)
    {
        $this->system_user = $object;
        $this->system_user_id = $object->id;
    }
    
    /**
     * Method get_system_user
     * Sample of usage: $lancamento->system_user->attribute;
     * @returns SystemUser instance
     */
    public function get_system_user()
    {
        // loads the associated object
        if (empty($this->system_user))
            $this->system_user = new SystemUser($this->system_user_id);
    
        // returns the associated object
        return $this->system_user;
    }
}