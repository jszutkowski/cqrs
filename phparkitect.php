<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\DependsOnlyOnTheseNamespaces;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\IsFinal;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\NotHaveNameMatching;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

/**
 * Note the trailing \* in every namespace pattern: without it, "App\*\Domain"
 * also matches a class merely *named* DomainSomething in another layer, and the
 * rule silently reports the wrong file.
 */
return static function (Config $config): void {
    $classSet = ClassSet::fromDir(__DIR__.'/src');

    // The rule the whole architecture rests on, written as an allow-list so a
    // newly introduced dependency has to be argued for rather than merely
    // slipping past a list of forbidden names.
    //
    // Two libraries are allowed in on purpose:
    //   beberlei/assert   — an assertion library, not a framework; expressive
    //                       invariant checks inside the model are worth more
    //                       than a dependency count of exactly zero.
    //   symfony/uid       — a value type for UUIDs. Hand-rolling one would add
    //                       code without adding independence.
    $domainIsolation = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\*\Domain\*'))
        ->should(new DependsOnlyOnTheseNamespaces([
            'App\Loyalty\Domain',
            'App\Shared\Domain',
            'Assert',
            'Symfony\Component\Uid',
        ]))
        ->because('the domain must stay free of frameworks and infrastructure');

    $applicationIsolation = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\*\Application\*'))
        ->should(new NotDependsOnTheseNamespaces([
            'App\*\Infrastructure\*',
            'App\*\UserInterface\*',
            'Doctrine',
            'Predis',
        ]))
        ->because('the application layer reaches infrastructure only through ports');

    // The audit found the old controller injecting MySqlWallets directly; this
    // rule is what stops that from coming back.
    $userInterfaceIsolation = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\*\UserInterface\*'))
        ->should(new NotDependsOnTheseNamespaces([
            'App\*\Infrastructure\*',
            'Doctrine',
            'Predis',
        ]))
        ->because('controllers depend on ports, never on a concrete adapter');

    $handlersAreFinal = Rule::allClasses()
        ->that(new HaveNameMatching('*Handler'))
        ->should(new IsFinal())
        ->because('handlers are not designed for inheritance');

    $projectionsAreFinal = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\*\Infrastructure\Projection\*'))
        ->should(new IsFinal())
        ->because('projections are leaf classes');

    // Aggregates expose intention-revealing behaviour, so a public setter is a
    // sign that a rule leaked out of the model into its callers.
    $domainHasNoSetters = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\*\Domain\*'))
        ->should(new NotHaveNameMatching('*Setter*'))
        ->because('domain state changes through named behaviour, not setters');

    $config
        ->add($classSet, $domainIsolation)
        ->add($classSet, $applicationIsolation)
        ->add($classSet, $userInterfaceIsolation)
        ->add($classSet, $handlersAreFinal)
        ->add($classSet, $projectionsAreFinal)
        ->add($classSet, $domainHasNoSetters);
};
