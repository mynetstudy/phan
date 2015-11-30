<?php declare(strict_types=1);
namespace Phan\CodeBase;

use \Phan\Database;
use \Phan\Language\Element\Property;
use \Phan\Language\FQSEN;
use \Phan\Language\FQSEN\FullyQualifiedClassName;
use \Phan\Model\Property as PropertyModel;

trait PropertyMap {

    /**
     * @var Property[][]
     * A map from FQSEN to name to a property
     */
    protected $property_map = [];

    /**
     * @return Property[][]
     * A map from FQSEN to name to property
     */
    public function getPropertyMap() : array {
        return $this->property_map;
    }

    /**
     * @return Property[]
     * A map from name to property
     */
    public function getPropertyMapForScope(FQSEN $fqsen) {
        if (empty($this->property_map[(string)$fqsen])) {
            return [];
        }

        return $this->property_map[(string)$fqsen];
    }

    /**
     * @param Property[] $property_map
     * A map from FQSEN to Property
     *
     * @return null
     */
    public function setPropertyMap(array $property_map) {
        $this->property_map = $property_map;
    }

    /**
     * @return bool
     */
    public function hasProperty(FQSEN $fqsen, string $name) : bool {
        return !empty($this->property_map[(string)$fqsen][$name]);
    }

    /**
     * @return Property
     * Get the property with the given FQSEN
     */
    public function getProperty(FQSEN $fqsen, string $name) : Property {
        return $this->property_map[(string)$fqsen][$name];
    }

    /**
     * @param Property $property
     * Any property
     *
     * @return null
     */
    public function addProperty(Property $property) {
        $this->addPropertyInScope(
            $property,
            $property->getFQSEN()->getFullyQualifiedClassName()
        );
    }

    /**
     * @param Property $property
     * Any property
     *
     * @param FullyQualifiedClassName $fqsen
     * The FQSEN to index the property by
     *
     * @return null
     */
    public function addPropertyInScope(
        Property $property,
        FullyQualifiedClassName $fqsen
    ) {
        $name = $property->getFQSEN()->getNameWithAlternateId();
        $this->property_map[(string)$fqsen][$name] = $property;
    }

    /**
     * Write each object to the database
     *
     * @return null
     */
    protected function storePropertyMap() {
        if (!Database::isEnabled()) {
            return;
        }

        foreach ($this->property_map as $scope => $map) {
            foreach ($map as $name => $property) {
                if (!$property->getContext()->isInternal()) {
                    (new PropertyModel(
                        $property, $scope, $name
                    ))->write(Database::get());
                }
            }
        }
    }

    /**
     * @return null
     */
    protected function flushPropertyWithScopeAndName(
        string $scope,
        string $name
    ) {
        // Remove it from the database
        if (Database::isEnabled()) {
            PropertyModel::delete(Database::get(),
                $scope . '|' . $name
            );
        }

        // Remove it from memory
        unset($this->property_map[$scope][$name]);
    }

}
