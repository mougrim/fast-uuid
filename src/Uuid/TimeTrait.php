<?php
declare(strict_types=1);

namespace Mougrim\FastUuid\Uuid;

use DateTimeInterface;
use Ramsey\Uuid\DeprecatedUuidMethodsTrait;
use Ramsey\Uuid\Rfc4122\TimeTrait as Rfc4122TimeTrait;
use Ramsey\Uuid\Uuid;
use function in_array;

/**
 * @author Mougrim <for-open-source@mougrim.io>
 */
trait TimeTrait
{
    private const DATE_TIME_SUPPORTED_VERSIONS = [
        Uuid::UUID_TYPE_TIME,
        Uuid::UUID_TYPE_DCE_SECURITY,
        Uuid::UUID_TYPE_REORDERED_TIME,
        Uuid::UUID_TYPE_UNIX_TIME
    ];

    use Rfc4122TimeTrait {
        Rfc4122TimeTrait::getDateTime as private timeTraitGetDateTime;
    }
    use DeprecatedUuidMethodsTrait {
        DeprecatedUuidMethodsTrait::getDateTime as deprecatedUuidMethodsTraitGetDateTime;
    }

    protected ?DateTimeInterface $dateTime = null;

    public function getDateTime(): DateTimeInterface
    {
        if ($this->dateTime === null) {
            if (in_array($this->fields->getVersion(), self::DATE_TIME_SUPPORTED_VERSIONS, strict: true)) {
                $this->dateTime = $this->timeTraitGetDateTime();
            } else {
                $this->dateTime = $this->deprecatedUuidMethodsTraitGetDateTime();
            }
        }

        return $this->dateTime;
    }
}
