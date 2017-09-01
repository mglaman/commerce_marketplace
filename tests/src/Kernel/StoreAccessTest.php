<?php

namespace Drupal\Tests\commerce_marketplace\Kernel;

use Drupal\commerce_marketplace\Plugin\EntityReferenceSelection\StoreSelection;
use Drupal\commerce_marketplace\StoreAccessControlHandler;
use Drupal\commerce_store\Entity\Store;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests store access
 *
 * @group commerce_marketplace
 */
class StoreAccessTest extends CommerceKernelTestBase {

  use EntityReferenceTestTrait;

  public static $modules = [
    'entity_test',
    'commerce_store',
    'commerce_marketplace',
  ];

  /**
   * The uid1 user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user1;

  /**
   * The uid2 user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user2;

  /**
   * The uid3 user, admin.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user3;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_store');
    $this->installConfig(['commerce_store', 'commerce_marketplace']);
    $this->user1 = $this->createUser([
      'roles' => ['commerce_marketplace_store_owner'],
    ]);
    $this->user2 = $this->createUser([
      'roles' => ['commerce_marketplace_store_owner'],
    ]);;
    $this->user3 = $this->createUser([
      'roles' => ['commerce_marketplace_store_owner'],
    ], ['view any commerce_store']);
  }

  /**
   * Tests that non-admin users can only create one store.
   */
  public function testSingleStoreCreateAccess() {
    $store_access_handler = $this->container->get('entity_type.manager')->getAccessControlHandler('commerce_store');
    $this->assertTrue($store_access_handler instanceof StoreAccessControlHandler);

    // Test create a store for uid 1 in parent::setUp.
    $this->assertFalse($store_access_handler->createAccess('online', $this->user1));
    $this->assertTrue($store_access_handler->createAccess('online', $this->user2));

    $store = Store::create([
      'type' => 'online',
      'uid' => $this->user2->id(),
      'name' => "Joe's Trinkets",
      'mail' => 'jimjoe@gmail.com',
      'address' => [
        'country_code' => 'US',
        'address_line1' => $this->randomString(),
        'locality' => $this->randomString(5),
        'administrative_area' => 'WI',
        'postal_code' => '53597',
      ],
      'default_currency' => 'US',
      'billing_countries' => [
        'US',
      ],
    ]);
    $store->save();

    $store_access_handler->resetCache();
    $this->assertFalse($store_access_handler->createAccess('online', $this->user2));
  }

  /**
   * Tests the entity reference selection access.
   */
  public function testEntityReferenceSelectionAccess() {
    $field_name = Unicode::strtolower($this->randomMachineName());
    $this->createEntityReferenceField('entity_test', 'entity_test', $field_name, $this->randomString(), 'commerce_store');
    $field_config = FieldConfig::loadByName('entity_test', 'entity_test', $field_name);
    $handler = $this->container->get('plugin.manager.entity_reference_selection')->getSelectionHandler($field_config);
    $this->assertInstanceOf(StoreSelection::class, $handler);

    $user2_store = Store::create([
      'type' => 'online',
      'uid' => $this->user2->id(),
      'name' => "Joe's Trinkets",
      'mail' => 'jimjoe@gmail.com',
      'address' => [
        'country_code' => 'US',
        'address_line1' => $this->randomString(),
        'locality' => $this->randomString(5),
        'administrative_area' => 'WI',
        'postal_code' => '53597',
      ],
      'default_currency' => 'US',
      'billing_countries' => [
        'US',
      ],
    ]);
    $user2_store->save();

    $this->container->get('current_user')->setAccount($this->user2);
    $handler = $this->container->get('plugin.manager.entity_reference_selection')->getSelectionHandler($field_config);
    $available_stores = $handler->getReferenceableEntities();
    $this->assertCount(1, $available_stores['online']);
    $store = reset($available_stores['online']);
    $this->assertEquals(Html::escape($user2_store->label()), $store);

    $this->container->get('current_user')->setAccount($this->user3);
    $handler = $this->container->get('plugin.manager.entity_reference_selection')->getSelectionHandler($field_config);
    $available_stores = $handler->getReferenceableEntities();
    $this->assertCount(2, $available_stores['online']);
  }

}
