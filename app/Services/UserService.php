<?php


namespace App\Services;


use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Builder;

class UserService extends BaseService
{
    protected $filter_fields;

    public function __construct(UserRepository $repo)
    {
        $this->repo = $repo;
        $this->filter_fields = [
            'name'  => ['type' => 'string'], 
            'email' => ['type' => 'string'], 
            'role'  => ['type' => 'string']
        ];
        $this->relation = [];
    }


}
