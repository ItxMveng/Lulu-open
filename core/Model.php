<?php
declare(strict_types=1);

abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = db();
    }
}