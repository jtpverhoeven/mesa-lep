<?php

class Client extends Model
{
    public function getAllFirsts() {}

    public function getAllActive()
    {
        $this->where('active', 1);

        return $this->search();
    }
}
