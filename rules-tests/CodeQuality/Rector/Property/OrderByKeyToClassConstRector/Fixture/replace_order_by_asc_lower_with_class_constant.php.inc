<?php

namespace Rector\Doctrine\Tests\CodeQuality\Rector\Property\OrderByKeyToClassConstRector\Fixture;

use Doctrine\ORM\Mapping as ORM;

class ReplaceOrderByAscWithClassConstant
{
    #[ORM\OrderBy(['createdAt' => 'asc'])]
    protected \DateTimeInterface $messages;
}
?>
