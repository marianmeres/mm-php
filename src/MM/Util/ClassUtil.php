<?php
/**
 * @author Marian Meres
 */
namespace MM\Util;

/**
 * Class ClassUtil
 * @package MM\Util
 */
class ClassUtil {
	/**
	 * Sets options which have normalized setter, or exists as public properties
	 * Normalized setter is considered "setSome" where "some" is key in options
	 * Feature: "under_scored" keys are also normalized to "camelCased"
	 */
	public static function setOptions(
		$object,
		array $options = null,
		bool $strict = true
	): int {
		if (!is_object($object)) {
			throw new \InvalidArgumentException('First argument must be an object');
		}

		$counter = 0;

		if (empty($options)) {
			return $counter;
		}

		// read public properties only via reflection, because
		// property_exists from php 5.3 is "independent of accessibility"
		$reflection = new \ReflectionClass($object);
		$publicProps = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
		$public = [];
		foreach ($publicProps as $prop) {
			$public[$prop->getName()] = 1;
		}

		foreach ($options as $_key => $value) {
			// normalize under_scored to CamelCased
			$key = str_replace('_', ' ', trim($_key));
			$key = str_replace(' ', '', ucwords($key));

			$setter = "set$key";
			$property = lcfirst($key);

			// setter? (highest priority)
			if (method_exists($object, $setter)) {
				$object->$setter($value);
				$counter++;
			}
			// public property?
			elseif (isset($public[$property])) {
				$object->$property = $value;
				$counter++;
			}
			//
			elseif ($strict) {
				throw new \RuntimeException(
					get_class($object) . ": Unknown option '$_key'",
				);
			}
		}

		return $counter;
	}

	/**
	 * Gets last segment (after last "_" or "\") from class or class name.
	 */
	public static function getLastSegmentName($fullNameOrClass): string {
		$name = is_object($fullNameOrClass)
			? get_class($fullNameOrClass)
			: (string) $fullNameOrClass;

		$_name = strtr($name, '\\', '_'); // normalize

		if (false !== ($pos = strrpos($_name, '_'))) {
			$name = substr($name, ++$pos);
		}

		return $name;
	}

	/**
	 * Gets list of **all** traits recursively which $class uses
	 * Taken from: http://www.php.net/manual/en/function.class-uses.php
	 */
	public static function classUsesDeep($class, bool $autoload = true): array {
		$traits = [];

		// Get traits of all parent classes
		do {
			$traits = array_merge(class_uses($class, $autoload), $traits);
		} while ($class = get_parent_class($class));

		// Get traits of all parent traits
		$traitsToSearch = $traits;
		while (!empty($traitsToSearch)) {
			$newTraits = class_uses(array_pop($traitsToSearch), $autoload);
			$traits = array_merge($newTraits, $traits);
			$traitsToSearch = array_merge($newTraits, $traitsToSearch);
		}

		foreach ($traits as $trait => $void) {
			$traits = array_merge(class_uses($trait, $autoload), $traits);
		}

		return array_unique($traits);
	}

	/**
	 * Skusi ticho autoloadnut classname.
	 */
	public static function classExists(string $className): bool {
		// nizsim set-om a restore-om error handlera riesime to, ze chceme
		// ticho vynutit nativny autoload ale uplne stisit via "@" ho zase
		// nechceme...
		set_error_handler(function ($n, $s, $f, $l) {
			restore_error_handler();
			throw new \ErrorException($s, 0, $n, $f, $l);
		});

		try {
			$result = class_exists($className, true);
			restore_error_handler();
			return $result;
		} catch (\ErrorException $e) {
			// chytame vsetko :(
			//return false;
			// update: tuto zistujem, ze za istych okolnosti treba hadzat dalej...
			// vid test: LoaderTest::testPhpErrorExceptionInClassIsRethrown
			// (ktory by inak bol ticho)
			throw $e;
		}
	}
}
