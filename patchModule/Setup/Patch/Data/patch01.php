<?PHP
		declare(strict_types=1);

    namespace Magento\DummyModule\Setup\Patch\Data;

    use Magento\Framework\Setup\Patch\DataPatchInterface;
    use Magento\Framework\Setup\Patch\PatchRevertableInterface;
    use Magento\Framework\App\State;
    use Magento\Catalog\Api\Data\ProductInterfaceFactory;
    use Magento\Catalog\Api\ProductRepositoryInterface;
    use Magento\Catalog\Model\Product;
    use Magento\Catalog\Model\Product\Attribute\Source\Status;
    use Magento\Catalog\Model\Product\Type;
    use Magento\Catalog\Model\Product\Visibility;
    use Magento\Eav\Setup\EavSetup;
    use Magento\Framework\Setup\ModuleDataSetupInterface;
    use Magento\Store\Model\StoreManagerInterface;
    use Magento\InventoryApi\Api\Data\SourceItemInterface;
    use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
    use Magento\InventoryApi\Api\SourceItemsSaveInterface;
    use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;


    class DataPatchClass
        implements DataPatchInterface, PatchRevertableInterface
    {
        protected ModuleDataSetupInterface $setup;

        protected ProductInterfaceFactory $productInterfaceFactory;

        protected ProductRepositoryInterface $productRepository;

        protected State $appState;

        protected EavSetup $eavSetup;

        protected StoreManagerInterface $storeManager;

        protected SourceItemInterfaceFactory $sourceItemFactory;

        protected SourceItemsSaveInterface $sourceItemsSaveInterface;

        protected CategoryLinkManagementInterface $categoryLink;

        protected array $sourceItems = [];

        public function __construct(
            ModuleDataSetupInterface $setup,
            ProductInterfaceFactory $productInterfaceFactory,
            ProductRepositoryInterface $productRepository,
            State $appState,
            StoreManagerInterface $storeManager,
            EavSetup $eavSetup,
            SourceItemInterfaceFactory $sourceItemFactory,
            SourceItemsSaveInterface $sourceItemsSaveInterface,
            CategoryLinkManagementInterface $categoryLink,
            CategoryCollectionFactory $categoryCollectionFactory
        )
        {
            $this->appState = $appState;
            $this->productInterfaceFactory = $productInterfaceFactory;
            $this->productRepository = $productRepository;
            $this->setup = $setup;
            $this->eavSetup = $eavSetup;
            $this->storeManager = $storeManager;
            $this->sourceItemFactory = $sourceItemFactory;
            $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
            $this->categoryLink = $categoryLink;
            $this->categoryCollectionFactory = $categoryCollectionFactory;

        }

        /**
         * {@inheritdoc}
         */
        public function apply()
        {
            $this->setup->startSetup();

            $this->appState->emulateAreaCode('adminhtml', [$this, 'execute']);
        }

        public function execute()
        {
            // create the product
            $product = $this->productInterfaceFactory->create();

            // check if the product already exists
            if ($product->getIdBySku('nike-black-balaclava')) {
                return;
            }

            // set default attributes...
            $attributeSetId = $this->eavSetup->getAttributeSetId(Product::ENTITY, 'Default');

            $product->setTypeId(Type::TYPE_SIMPLE)
                ->setAttributeSetId($attributeSetId)
                ->setName('Nike Balaclava')
                ->setSku('nike-black-balaclava')
                        ->setUrlKey('nikebalaclava')
                ->setPrice(9.99)
                ->setVisibility(Visibility::VISIBILITY_BOTH)
                ->setStatus(Status::STATUS_ENABLED);

            // save the product to the repository
            $product = $this->productRepository->save($product);

            $categoryTitles = ['Men'];
            $categoryIds = $this->categoryCollectionFactory->create()
                ->addAttributeToFilter('name', ['in' => $categoryTitles])
                ->getAllIds();

            $this->categoryLink->assignProductToCategories($product->getSku(), $categoryIds);

            $this->setup->endSetup();
        }

        /**
         * {@inheritdoc}
         */
        public static function getDependencies()
        {
            return [
                SomeDependency::class
            ];
        }

        public function revert()
        {
        }

        /**
         * {@inheritdoc}
         */
        public function getAliases()
        {
            return [];
        }
    }
