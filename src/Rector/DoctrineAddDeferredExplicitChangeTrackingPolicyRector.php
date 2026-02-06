<?php

namespace CodedMonkey\Dirigent\Rector;

use Doctrine\ORM\Mapping\ChangeTrackingPolicy;
use Doctrine\ORM\Mapping\Entity;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\GroupUse;
use Rector\Naming\Naming\UseImportsResolver;
use Rector\Rector\AbstractRector;
use Rector\ValueObject\PhpVersionFeature;
use Rector\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Adds an ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT') attribute to all Doctrine entities.
 */
final class DoctrineAddDeferredExplicitChangeTrackingPolicyRector extends AbstractRector implements MinPhpVersionInterface
{
    private const string CHANGE_TRACKING_POLICY_ATTRIBUTE = ChangeTrackingPolicy::class;
    private const string DOCTRINE_ORM_MAPPING = 'Doctrine\ORM\Mapping';
    private const string ENTITY_ATTRIBUTE = Entity::class;

    public function __construct(
        private readonly UseImportsResolver $useImportsResolver,
    ) {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT") to Doctrine entities',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
                    use Doctrine\ORM\Mapping as ORM;

                    #[ORM\Entity]
                    class Product
                    {
                    }
                    CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
                    use Doctrine\ORM\Mapping as ORM;

                    #[ORM\Entity]
                    #[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
                    class Product
                    {
                    }
                    CODE_SAMPLE,
                ),
            ],
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof Class_ || $node->isAnonymous() || $node->isAbstract()) {
            return null;
        }

        // Check if the class has an Entity attribute
        $entityAttribute = $this->findEntityAttribute($node);
        if (null === $entityAttribute) {
            return null;
        }

        // Skip readonly entities - they don't need change tracking
        if ($this->isReadOnlyEntity($entityAttribute)) {
            return null;
        }

        // Check if the class already has ORM\ChangeTrackingPolicy attribute
        if ($this->hasChangeTrackingPolicyAttribute($node)) {
            return null;
        }

        // Find the position of the Entity attribute to insert after it
        $entityAttrGroupIndex = $this->findEntityAttributeGroupIndex($node);

        // Create the ChangeTrackingPolicy attribute using the same alias as the imports
        $changeTrackingPolicyAttrGroup = $this->createChangeTrackingPolicyAttributeGroup();

        // Insert the new attribute group after the Entity attribute
        array_splice($node->attrGroups, $entityAttrGroupIndex + 1, 0, [$changeTrackingPolicyAttrGroup]);

        return $node;
    }

    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::ATTRIBUTES;
    }

    private function findEntityAttribute(Class_ $node): ?Attribute
    {
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                if ($this->isName($attribute, self::ENTITY_ATTRIBUTE)) {
                    return $attribute;
                }
            }
        }

        return null;
    }

    private function isReadOnlyEntity(Attribute $entityAttribute): bool
    {
        foreach ($entityAttribute->args as $arg) {
            if ($arg->name instanceof Identifier && 'readOnly' === $arg->name->toString()) {
                // Check if the value is true
                if ($arg->value instanceof ConstFetch && $this->isName($arg->value, 'true')) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasChangeTrackingPolicyAttribute(Class_ $node): bool
    {
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                if ($this->isName($attribute, self::CHANGE_TRACKING_POLICY_ATTRIBUTE)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function findEntityAttributeGroupIndex(Class_ $node): int
    {
        foreach ($node->attrGroups as $index => $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                if ($this->isName($attribute, self::ENTITY_ATTRIBUTE)) {
                    return $index;
                }
            }
        }

        return 0;
    }

    private function createChangeTrackingPolicyAttributeGroup(): AttributeGroup
    {
        // Find the alias used for Doctrine\ORM\Mapping (e.g., "ORM")
        $alias = $this->findDoctrineOrmMappingAlias();

        // Create the attribute name using the alias
        if (null !== $alias) {
            $attributeName = new Name([$alias, 'ChangeTrackingPolicy']);
        } else {
            // Fallback to fully qualified name if no alias found
            $attributeName = new FullyQualified(self::CHANGE_TRACKING_POLICY_ATTRIBUTE);
        }

        $arg = new Arg(new String_('DEFERRED_EXPLICIT'));
        $attribute = new Attribute($attributeName, [$arg]);

        return new AttributeGroup([$attribute]);
    }

    private function findDoctrineOrmMappingAlias(): ?string
    {
        $uses = $this->useImportsResolver->resolve();

        foreach ($uses as $use) {
            if ($use instanceof GroupUse) {
                continue;
            }

            foreach ($use->uses as $useUse) {
                $useName = $useUse->name->toString();
                if (self::DOCTRINE_ORM_MAPPING === $useName && $useUse->alias instanceof Identifier) {
                    return $useUse->alias->toString();
                }
            }
        }

        return null;
    }
}
