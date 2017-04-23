<?php
namespace MM\Util\ClassUtilTest;

// hierarchia traitov
trait HeyTrait {
    public function hey() {
        echo "hou";
    }
}

trait HeyTrait2 {
    use HeyTrait;
}

trait HeyTrait3 {
    use HeyTrait2;
}

// hierarchia klasov
abstract class RodicTop {
    use HeyTrait3; // posledny v hierarchii traitov
}

abstract class Rodic extends RodicTop {
}

class Potomok extends Rodic {
}