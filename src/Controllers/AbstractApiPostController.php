<?php

declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use ReflectionClass;
use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use RZ\Roadiz\Core\Entities\NodesSources;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Themes\AbstractApiTheme\AbstractApiThemeApp;
use Themes\AbstractApiTheme\Serialization\SerializationContextFactoryInterface;

abstract class AbstractApiPostController extends AbstractApiThemeApp
{
    abstract protected function getSerializationGroups(): array;

    abstract protected function getDeserializationGroups(): array;

    abstract protected function validateAccess(): void;

    /**
     * @return class-string<PersistableInterface>
     */
    abstract protected function getEntityClassname(): string;

    /**
     * @param PersistableInterface $entity
     * @return Event
     */
    abstract protected function getPreCreatedEvent($entity): Event;

    protected function getDoctrine(): ManagerRegistry
    {
        return $this->get(ManagerRegistry::class);
    }

    protected function getEntityManager(): ObjectManager
    {
        return $this->getDoctrine()->getManager();
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \ReflectionException
     */
    public function defaultAction(Request $request)
    {
        $this->validateAccess();

        if (\in_array(strtolower($request->getContentType() ?? ''), ['json', 'application/json'])) {
            $jsonContent = (string) $request->getContent();
            if (empty($jsonContent) || trim($jsonContent) === '') {
                throw new BadRequestHttpException('Request content is empty.');
            }
            /** @var SerializerInterface $serializer */
            $serializer = $this->get('serializer');
            /** @var PersistableInterface $entity */
            $entity = $serializer->deserialize(
                $jsonContent,
                $this->getEntityClassname(),
                'json',
                $this->getDeserializationContext()
            );

            return $this->handleEntity($request, $entity, $serializer);
        }

        throw new BadRequestHttpException('Content type must be application/json');
    }

    protected function handleEntity(Request $request, PersistableInterface $entity, SerializerInterface $serializer): Response
    {
        $entity = $this->normalizeEntity($entity);
        $this->validateEntity($entity);

        $this->get('dispatcher')->dispatch($this->getPreCreatedEvent($entity));
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        if ($entity instanceof NodesSources) {
            $msg = $this->getTranslator()->trans(
                'entity.%name%.%type%.has_been_created',
                [
                    '%name%' => $entity->getTitle(),
                    '%type%' => $this->getEntityClassname(),
                ]
            );
            $this->get('logger')->info($msg, ['source' => $entity]);
        } else {
            $msg = $this->getTranslator()->trans(
                'entity.%name%.%type%.has_been_created',
                [
                    '%name%' => (string) $entity,
                    '%type%' => $this->getEntityClassname(),
                ]
            );
            $this->get('logger')->info($msg);
        }

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

    protected function getSerializationContext(): SerializationContext
    {
        $context = $this->get(SerializationContextFactoryInterface::class)->create();
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

    /**
     * @param mixed $entity
     * @return mixed
     */
    protected function normalizeEntity($entity)
    {
        // Do nothing on this entity
        return $entity;
    }
}
