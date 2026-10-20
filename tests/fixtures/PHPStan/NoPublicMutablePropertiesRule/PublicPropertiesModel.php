<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoPublicMutablePropertiesRule;

use Illuminate\Database\Eloquent\Model;

final class PublicPropertiesModel extends Model
{

    /**
     * @var bool
     */
    public $timestamps = false;

}
