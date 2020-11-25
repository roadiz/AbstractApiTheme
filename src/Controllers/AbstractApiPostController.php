<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use Doctrine\Common\Annotations\AnnotationReader;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use ReflectionClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Themes\AbstractApiTheme\AbstractApiThemeApp;

abstract class AbstractApiPostController extends AbstractApiThemeApp
{
    abstract protected function getSerializationGroups(): array;

    abstract protected function getDeserializationGroups(): array;

    abstract protected function validateAccess(): void;

    abstract protected function getEntityClassname(): string;

    abstract protected function getPreCreatedEvent($entity): Event;

    public function defaultAction(Request $request)
    {
        $this->validateAccess();

        if (\in_array(strtolower($request->getContentType()), ['json', 'application/json'])) {
            $jsonContent = $request->getContent();
            /** @var SerializerInterface $serializer */
            $serializer = $this->get('serializer');
            $entity = $serializer->deserialize(
                $jsonContent,
                $this->getEntityClassname(),
                'json',
                $this->getDeserializationContext()
            );

            $this->validateEntity($entity);

            $this->get('dispatcher')->dispatch($this->getPreCreatedEvent($entity));
            $this->get('em')->persist($entity);
            $this->get('em')->flush();

            $response = new JsonResponse(
                $serializer->serialize(
                    $entity,
                    'json',
                    $this->getSerializationContext()
                ),
                JsonResponse::HTTP_OK,
                [],
                true
            );

            return $this->makeResponseCachable(
                $request,
                $response,
                0
            );
        }

        throw new BadRequestHttpException('Content type must be application/json');
    }

    protected function getSerializationContext(): SerializationContext
    {
        $context = SerializationContext::create()
            ->enableMaxDepthChecks();
        if (count($this->getSerializationGroups()) > 0) {
            $context->setGroups($this->getSerializationGroups());
        }

        return $context;
    }

    protected function getDeserializationContext(): DeserializationContext
    {
        $context = DeserializationContext::create()
            ->enableMaxDepthChecks();
        if (count($this->getDeserializationGroups()) > 0) {
            $context->setGroups($this->getDeserializationGroups());
        }

        return $context;
    }
    /**
     * Validate entity using its class and properties annotations.
     *
     * @param mixed $entity
     * @throws \ReflectionException
     */
    protected function validateEntity($entity): void
    {
        $reader = new AnnotationReader();
        $reflection = new ReflectionClass(get_class($entity));
        $classAnnotations = $reader->getClassAnnotations($reflection);
        $classConstraints = array_filter($classAnnotations, function ($annotation) {
            return $annotation instanceof Constraint;
        });

        /** @var ValidatorInterface $validator */
        $validator = $this->get('formValidator');
        /** @var ConstraintViolation[] $violations */
        $violations = $validator->validate($entity, $classConstraints);

        if (0 !== count($violations)) {
            throw new BadRequestHttpException($violations[0]->getMessage() . ' ('.$violations[0]->getInvalidValue().')');
        }
        /*
         * Validate each properties
         */
        foreach ($reflection->getProperties() as $reflectionProperty) {
            $propertiesAnnotations = $reader->getPropertyAnnotations($reflectionProperty);
            $propertiesAnnotations = array_filter($propertiesAnnotations, function ($annotation) {
                return $annotation instanceof Constraint;
            });
            /*
             * need to enforce access to properties to prevent
             * accessing via getters.
             */
            $reflectionProperty->setAccessible(true);
            /** @var ConstraintViolation[] $violations */
            $violations = $validator->validate($reflectionProperty->getValue($entity), $propertiesAnnotations);
            if (0 !== count($violations)) {
                throw new BadRequestHttpException($violations[0]->getMessage() . ' ('.$reflectionProperty->getName().')');
            }
        }
    }
}
