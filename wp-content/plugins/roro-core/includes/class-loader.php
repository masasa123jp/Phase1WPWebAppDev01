<?php
/**
 * Project‑specific PSR‑4 autoloader.
 *
 * @link https://www.php-fig.org/psr/psr-4/  PSR‑4 spec
 *
 * Usage:
 *   $loader = new \RoroCore\Loader();
 *   $loader->addNamespace('RoroCore', __DIR__);
 *   $loader->register();
 *
 * @package RoroCore
 */

namespace RoroCore;

class Loader {
	/**
	 * @var array<string,array<int,string>> $prefixes [namespacePrefix => [baseDirs]]
	 */
	protected array $prefixes = [];

	/**
	 * Register loader with SPL stack.
	 */
	public function register(): void {
		spl_autoload_register([$this, 'loadClass']);
	}

	/**
	 * Add a baseDir for namespace prefix.
	 */
	public function addNamespace(string $prefix, string $baseDir, bool $prepend = false): void {
		$prefix   = trim($prefix, '\\') . '\\';
		$baseDir  = rtrim($baseDir, DIRECTORY_SEPARATOR) . '/';

		if (!isset($this->prefixes[$prefix])) {
			$this->prefixes[$prefix] = [];
		}
		$prepend ? array_unshift($this->prefixes[$prefix], $baseDir)
		         : array_push($this->prefixes[$prefix], $baseDir);
	}

	/**
	 * Load class file if it exists.
	 */
	public function loadClass(string $class): bool {
		$prefix = $class;

		while (false !== $pos = strrpos($prefix, '\\')) {
			$prefix         = substr($class, 0, $pos + 1);
			$relativeClass  = substr($class, $pos + 1);

			$file = $this->loadMappedFile($prefix, $relativeClass);
			if ($file) {
				return true;
			}
			$prefix = rtrim($prefix, '\\');
		}
		return false;
	}

	/**
	 * Map namespace prefix + relative class to file path.
	 */
	protected function loadMappedFile(string $prefix, string $relative): ?string {
		if (!isset($this->prefixes[$prefix])) {
			return null;
		}
		foreach ($this->prefixes[$prefix] as $baseDir) {
			$file = $baseDir . str_replace('\\', '/', $relative) . '.php';
			if (is_file($file)) {
				require $file;
				return $file;
			}
		}
		return null;
	}
}
