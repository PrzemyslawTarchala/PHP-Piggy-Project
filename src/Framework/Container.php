<?php

declare(strict_types=1);

namespace Framework;

use ReflectionClass, ReflectionNamedType;
use Framework\Exceptions\ContainerException;

class Container 
{
	private array $definitions = [];

	public function addDefinitions(array $newDefinitions)
	{
		$this->definitions = array_merge($this->definitions, $newDefinitions); //Merging arrays ('...' unpacking array)
	}

	public function resolve(string $className)
	{
		$reflectionClass = new ReflectionClass($className);

		if(!$reflectionClass->isInstantiable()){
			throw new ContainerException("Class {$className} is not instantiable");
		}

		$constuctor = $reflectionClass->getConstructor();
		if(!$constuctor){
			return new $className;
		}

		$params = $constuctor->getParameters();
		if(count($params) === 0){
			return new $className;
		}

		$dependiences = [];

		foreach($params as $param){
			$name = $param->getName();
			$type = $param->getType();
			if(!$type){
				throw new ContainerException("Failed to resolve class {$className} becouse param {$name} is missing a type hint.");
			}
			
			if(!$type instanceof ReflectionNamedType || $type->isBuiltin()){
				throw new ContainerException("Failed to resolve class {$className} becouse invalid param name.");
			}
			$dependiences[] = $this->get($type->getName());
		}

		return $reflectionClass->newInstanceArgs($dependiences);
	}

	public function get(string $id)
	{
		if(!array_key_exists($id, $this->definitions)){
			throw new ContainerException("Class {$id} does not exist in container.");
		}

		$factory = $this->definitions[$id];
		$dependency = $factory();
		return $dependency;
	}
}