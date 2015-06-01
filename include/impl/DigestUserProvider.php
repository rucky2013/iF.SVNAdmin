<?php
class DigestUserProvider extends SearchableUserProvider {
  private $_passwd = null;

  public function __construct($id) {
    parent::__construct($id);
    $this->_flags[] = Provider::FLAG_EDITABLE;
  }

  public function initialize(SVNAdminEngine $engine, $config) {
    $this->_passwd = new Htdigest($config["file"], $config["realm"]);
    if (!$this->_passwd->init()) {
      $this->_passwd->error();
      return false;
    }
    return true;
  }

  public function getUsers($offset = 0, $num = -1) {
    $list = new ItemList();

    $users = $this->_passwd->getUserList();
    $usersCount = count($users);

    $listItems = array ();
    $begin = (int) $offset;
    $end = (int) $num === -1 ? $usersCount : (int) $offset + (int) $num;
    for ($i = $begin; $i < $end && $i < $usersCount; ++$i) {
      $username = $users[$i];
      $obj = new User();
      $obj->initialize($username, $username);
      $listItems[] = $obj;
    }
    $list->initialize($listItems, $usersCount > $end);
    return $list;
  }

  public function create($name, $password) {
    if (!$this->_passwd->createUser($name, $password)) {
      error_log("Can not create user (message=" . $this->_passwd->error() . "; name=" . $name . ")");
      return null;
    }
    if (!$this->_passwd->writeToFile()) {
      error_log("Can not write file (message=" . $this->_passwd->error() . ")");
      return null;
    }
    $o = new User();
    $o->initialize($name, $name);
    return $o;
  }

  public function delete($id) {
    if (empty($id)) {
      return false;
    }
    if (!$this->_passwd->deleteUser($id)) {
      return false;
    }
    if (!$this->_passwd->writeToFile()) {
      return null;
    }
    return true;
  }

  public function changePassword($id, $password) {
    if (empty($id) || empty($password)) {
      return false;
    }
    if (!$this->_passwd->changePassword($id, $password)) {
      return false;
    }
    if (!$this->_passwd->writeToFile()) {
      return null;
    }
    return true;
  }

}
?>