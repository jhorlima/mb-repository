#Repositório - MocaBonita

Modulo de Repositório para o MocaBonita

```sh
$ composer require jhorlima/mb-repository
``` 

```php
<?php

namespace Projeto\Repository;

use Projeto\Model\Usuarios;
use MbRepository\BaseRepository;

class UsuariosRepository extends BaseRepository
{
    public function model()
    {
        return Usuarios::class;
    }
}
```

Cada parametro pode ser obtido através do método 

```php
<?php

use Projeto\Repository\UsuariosRepository;


$repository = new UsuariosRepository();

var_dump($repository->all());

```
