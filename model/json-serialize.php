<?php
class Annotation
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $arguments = [];

    /**
     * @param string $name
     */
    public function setName(string $name): void {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName() : string {
        return $this->name;
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function addArgument(string $key, string $value): void {
        $this->arguments[$key] = $value;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function hasArgument(string $key): bool {
        return isset($this->arguments[$key]);
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function getArgument(string $key) {
        return $this->arguments[$key] ?? null;
    }
}



trait JsonSerializeTrait
{
    /**
     * @var array
     */
    private static $exportPropertyList;


    /**
     * @return array
     * @throws \ReflectionException
     */
    public function jsonSerialize(): array
    {
        return $this->getJsonExportArray();
    }

    /**
     * @return array
     * @throws \ReflectionException
     */
    public function getJsonExportArray() : array
    {
        $className = get_class($this);
        if (!isset(self::$exportPropertyList[$className])) {
            $this->parsePropertyAnnotation();
        }
        $export = [];
        $reflection = new ReflectionClass($className);
        foreach (self::$exportPropertyList[$className] as $propertyName => $option) {
            if (!$option) {
                $reflectionProperty = $reflection->getProperty($propertyName);
                $reflectionProperty->setAccessible(true);
                $export[$propertyName] = $reflectionProperty->getValue($this);
                continue;
            }

            $reflectionProperty = $reflection->getProperty($propertyName);
            $reflectionProperty->setAccessible(true);
            $value = $reflectionProperty->getValue($this);

            if (!empty($option['format'])) {
                switch (true) {
                    case $value instanceof \DateTime:
                        $export[$propertyName] = $value->format($option['format']);
                        break;
                }
            }
        }

        return $export;
    }


    /**
     * @throws \ReflectionException
     */
    public function parsePropertyAnnotation(): void
    {

        $className = get_class($this);
        $reflectionClass = new ReflectionClass($className);

        self::$exportPropertyList[$className] = [];


        foreach ($reflectionClass->getProperties() as $property) {
            $reflection = $reflectionClass->getProperty($property->getName());
            $annotations = $this->parseAnnotations($reflection->getDocComment());
            foreach($annotations as $annotation) {
                if ($annotation->getName() !== 'JSON') {
                    continue;
                }
                $option = [];
                if ($annotation->getArgument('export'))
                {
                    if ($annotation->hasArgument('format')) {
                        $option = [
                            'format' => $annotation->getArgument('format')
                        ];
                    }

                    self::$exportPropertyList[$className][$property->getName()] = $option;
                }
                break;
            }

        }
    }

    /**
     * @param string $rawString
     * @return array
     */
    public function parseAnnotations(string $rawString): array {
        $annotationSet = [];
        preg_match_all('/[@](\w+)[(]([0-9a-zA-Z_=|"\'#*+~?`!§$%&\/\[\]\\\,.\s]*)[)]?/', $rawString, $annotationSet);
        array_shift($annotationSet);
        var_dump($annotationSet);
        $annotation = new Annotation();
        $totalCount = count($annotationSet);
        for ($i = 0, $c = count($annotationSet[0]); $i < $c ;$i++) {
            for ($x = 0, $k = $totalCount; $i < $k; $x++) {
                $test = 12;
            }
        }
        return $annotationSet;
    }

}


class MyDataModel implements JsonSerializable {

    use JsonSerializeTrait;

    /**
     * @JSON(export=true)
     * @var int
     */
    private $id;

    /**
     * @JSON(export=true, format="Y-m-d")
     * @var DateTime
     */
    private $otherProperty;

    public function __construct()
    {
        $this->otherProperty = new DateTime();
    }
}

echo json_encode(new MyDataModel());