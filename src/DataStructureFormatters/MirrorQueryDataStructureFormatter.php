<?php
namespace PoP\APIMirrorQuery\DataStructureFormatters;
use PoP\ComponentModel\Facades\Schema\FieldQueryInterpreterFacade;
use PoP\ComponentModel\Facades\Schema\FeedbackMessageStoreFacade;
use PoP\ComponentModel\DataStructure\AbstractDataStructureFormatter;
use PoP\ComponentModel\Engine_Vars;

class MirrorQueryDataStructureFormatter extends AbstractDataStructureFormatter
{
    public const NAME = 'mirrorquery';
    public static function getName() {
        return self::NAME;
    }

    protected function getFields()
    {
        // Allow REST to override with default fields
        $vars = Engine_Vars::getVars();
        return $vars['query'];
    }

    public function getFormattedData($data)
    {
        // Re-create the shape of the query by iterating through all dbObjectIDs and all required fields,
        // getting the data from the corresponding dbKeyPath
        $ret = [];
        if ($fields = $this->getFields()) {
            $databases = $data['databases'] ?? [];
            $datasetModuleData = $data['datasetmoduledata'] ?? [];
            foreach ($datasetModuleData as $moduleName => $dbObjectIDs) {
                $dbKeyPaths = $data['datasetmodulesettings'][$moduleName]['dbkeys'] ?? [];
                $dbObjectIDorIDs = $dbObjectIDs['dbobjectids'];
                $this->addData($ret, $fields, $databases, $dbObjectIDorIDs, 'id', $dbKeyPaths, false);
            }
        }

        return $ret;
    }
    // GraphQL/REST cannot have getExtraRoutes()!!!!! Because the fields can't be applied to different resources! (Eg: author/leo/ and author/leo/?route=posts)
    // public function getFormattedData($data)
    // {
    //     // Re-create the shape of the query by iterating through all dbObjectIDs and all required fields,
    //     // getting the data from the corresponding dbKeyPath
    //     $ret = [];
    //     if ($fields = $this->getFields()) {
    //         $engine = EngineFacade::getInstance();
    //         list($has_extra_routes) = $engine->listExtraRouteVars();
    //         $vars = \PoP\ComponentModel\Engine_Vars::getVars();
    //         $dataoutputmode = $vars['dataoutputmode'];

    //         $databases = $data['databases'] ?? [];
    //         $datasetModuleData = $data['datasetmoduledata'] ?? [];
    //         $datasetModuleSettings = $data['datasetmodulesettings'] ?? [];
    //         if ($dataoutputmode == GD_URLPARAM_DATAOUTPUTMODE_SPLITBYSOURCES) {
    //             if ($has_extra_routes) {
    //                 $datasetModuleData = array_merge_recursive(
    //                     $datasetModuleData['immutable'] ?? [],
    //                     ($has_extra_routes ? array_values($datasetModuleData['mutableonmodel'])[0] : $datasetModuleData['mutableonmodel']) ?? [],
    //                     ($has_extra_routes ? array_values($datasetModuleData['mutableonrequest'])[0] : $datasetModuleData['mutableonrequest']) ?? []
    //                 );
    //                 $datasetModuleSettings = array_merge_recursive(
    //                     $datasetModuleSettings['immutable'] ?? [],
    //                     ($has_extra_routes ? array_values($datasetModuleSettings['mutableonmodel'])[0] : $datasetModuleSettings['mutableonmodel']) ?? [],
    //                     ($has_extra_routes ? array_values($datasetModuleSettings['mutableonrequest'])[0] : $datasetModuleSettings['mutableonrequest']) ?? []
    //                 );
    //             }
    //         } elseif ($dataoutputmode == GD_URLPARAM_DATAOUTPUTMODE_COMBINED) {
    //             if ($has_extra_routes) {
    //                 $datasetModuleData = array_values($datasetModuleData)[0];
    //                 $datasetModuleSettings = array_values($datasetModuleSettings)[0];
    //             }
    //         }
    //         foreach ($datasetModuleData as $moduleName => $dbObjectIDs) {
    //             $dbKeyPaths = $datasetModuleSettings[$moduleName]['dbkeys'] ?? [];
    //             $dbObjectIDorIDs = $dbObjectIDs['dbobjectids'];
    //             $this->addData($ret, $fields, $databases, $dbObjectIDorIDs, 'id', $dbKeyPaths, false);
    //         }
    //     }

    //     return $ret;
    // }

    protected function addData(&$ret, $fields, &$databases, $dbObjectIDorIDs, $dbObjectKeyPath, &$dbKeyPaths, $concatenateField = true)
    {
        // Property fields have numeric key only. From them, obtain the fields to print for the object
        $propertyFields = array_filter(
            $fields,
            function ($key) {
                return is_numeric($key);
            },
            ARRAY_FILTER_USE_KEY
        );
        // All other fields must be nested, to keep fetching data for the object relationships
        $nestedFields = array_diff($fields, $propertyFields);

        // The results can be a single ID or value, or an array of IDs
        if (is_array($dbObjectIDorIDs)) {
            foreach ($dbObjectIDorIDs as $dbObjectID) {
                // Add a new array for this DB object, where to return all its properties
                $ret[] = [];
                $dbObjectRet = &$ret[count($ret)-1];
                $this->addDBObjectData($dbObjectRet, $propertyFields, $nestedFields, $databases, $dbObjectID, $dbObjectKeyPath, $dbKeyPaths, $concatenateField);
            }
        }
        else {
            $dbObjectID = $dbObjectIDorIDs;
            $this->addDBObjectData($ret, $propertyFields, $nestedFields, $databases, $dbObjectID, $dbObjectKeyPath, $dbKeyPaths, $concatenateField);
        }
    }

    protected function addDBObjectData(&$dbObjectRet, $propertyFields, $nestedFields, &$databases, $dbObjectID, $dbObjectKeyPath, &$dbKeyPaths, $concatenateField)
    {
        // Add all properties requested from the object
        $dbKey = $dbKeyPaths[$dbObjectKeyPath];
        $dbObject = $databases[$dbKey][$dbObjectID] ?? [];
        foreach ($propertyFields as $propertyField) {
            // Only if the property has been set (in case of dbError it is not set)
            $propertyFieldOutputKey = FieldQueryInterpreterFacade::getInstance()->getFieldOutputKey($propertyField);
            if (array_key_exists($propertyFieldOutputKey, $dbObject)) {
                $dbObjectRet[$propertyFieldOutputKey] = $dbObject[$propertyFieldOutputKey];
            }
        }

        // Add the nested levels
        foreach ($nestedFields as $nestedField => $nestedPropertyFields) {

            $nestedFieldOutputKey = FieldQueryInterpreterFacade::getInstance()->getFieldOutputKey($nestedField);
            // If the key doesn't exist, then do nothing. This supports the "skip output if null" behaviour: if it is to be skipped, there will be no value (which is different than a null)
            if (array_key_exists($nestedFieldOutputKey, $dbObject)) {
                // If it's null, directly assign the null to the result
                if (is_null($dbObject[$nestedFieldOutputKey])) {
                    $dbObjectRet[$nestedFieldOutputKey] = null;
                } else {
                    // Watch out! If the property has already been loaded from a previous iteration, in some cases it can create trouble!
                    // But make sure that there truly are subproperties! It could also be a schemaError.
                    // Eg: ?query=posts.title.id, then no need to transform "title" from string to {"id" => ...}
                    if (FeedbackMessageStoreFacade::getInstance()->getSchemaErrorsForField($dbKey, $nestedField)) {
                        $dbObjectRet[$nestedFieldOutputKey] = $dbObject[$nestedFieldOutputKey];
                    } else {
                        // The first field, "id", needs not be concatenated. All the others do need
                        $nextField = ($concatenateField ? $dbObjectKeyPath.'.' : '').$nestedFieldOutputKey;

                        // Add a new subarray for the nested property
                        $dbObjectNestedPropertyRet = &$dbObjectRet[$nestedFieldOutputKey];

                        // If it is an empty array, then directly add an empty array as the result
                        if (is_array($dbObject[$nestedFieldOutputKey]) && empty($dbObject[$nestedFieldOutputKey])) {
                            $dbObjectRet[$nestedFieldOutputKey] = [];
                        } else {
                            if (!empty($dbObjectNestedPropertyRet)) {
                                // 1. If we load a relational property as its ID, and then load properties on the corresponding object, then it will fail because it will attempt to add a property to a non-array element
                                // Eg: /posts/api/graphql/?query=id|author,author.name will first return "author => 1" and on the "1" element add property "name"
                                // Then, if this situation happens, simply override the ID (which is a scalar value, such as an int or string) with an object with the 'id' property
                                if (!is_array($dbObjectNestedPropertyRet)) {
                                    $dbObjectRet[$nestedFieldOutputKey] = [
                                        'id' => $dbObjectRet[$nestedFieldOutputKey],
                                    ];
                                } else {
                                    // 2. If the previous iteration loaded an array of IDs, then override this value with an empty array and initialize the ID again to this object, through adding property 'id' on the next iteration
                                    // Eg: /api/graphql/?query=tags,tags.name
                                    $dbObjectRet[$nestedFieldOutputKey] = [];
                                    if (!in_array('id', $nestedPropertyFields)) {
                                        array_unshift($nestedPropertyFields, 'id');
                                    }
                                }
                            }
                            $this->addData($dbObjectNestedPropertyRet, $nestedPropertyFields, $databases, $dbObject[$nestedFieldOutputKey], $nextField, $dbKeyPaths);
                        }
                    }
                }
            }
        }
    }
}
