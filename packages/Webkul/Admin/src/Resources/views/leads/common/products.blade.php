@php($canCreateProduct = bouncer()->hasPermission('products.create.quick-create'))

<v-product-list
    :data="products"
    :can-create-product="@json($canCreateProduct)"
></v-product-list>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-product-list-template"
    >
        <div class="flex flex-col gap-4">
            {!! view_render_event('admin.leads.create.products.form_controls.table.before') !!}

            <div class="block w-full">
                <!-- Table -->
                <x-admin::table>
                    {!! view_render_event('admin.leads.create.products.form_controls.table.head.before') !!}

                    <!-- Table Head -->
                    <x-admin::table.thead>
                        <x-admin::table.thead.tr>
                            <x-admin::table.th>
                                @lang('admin::app.leads.common.products.product-name')
                            </x-admin::table.th>

                            <x-admin::table.th class="text-center">
                                @lang('admin::app.leads.common.products.quantity')
                            </x-admin::table.th>

                            <x-admin::table.th class="text-center">
                                @lang('admin::app.leads.common.products.price')
                            </x-admin::table.th>

                            <x-admin::table.th class="text-center">
                                @lang('admin::app.leads.common.products.amount')
                            </x-admin::table.th>

                            <x-admin::table.th class="text-right">
                                @lang('admin::app.leads.common.products.action')
                            </x-admin::table.th>
                        </x-admin::table.thead.tr>
                    </x-admin::table.thead>

                    {!! view_render_event('admin.leads.create.products.form_controls.table.head.after') !!}

                    {!! view_render_event('admin.leads.create.products.form_controls.table.body.before') !!}

                    <!-- Table Body -->
                    <x-admin::table.tbody>
                        {!! view_render_event('admin.leads.create.products.form_controls.table.body.product_item.before') !!}

                        <!-- Product Item Vue Component -->
                        <v-product-item
                            v-for='(product, index) in products'
                            :product="product"
                            :key="index"
                            :index="index"
                            :can-create-product="canCreateProduct"
                            @onRemoveProduct="removeProduct($event)"
                        ></v-product-item>

                        {!! view_render_event('admin.leads.create.products.form_controls.table.body.product_item.after') !!}
                    </x-admin::table.tbody>

                    {!! view_render_event('admin.leads.create.products.form_controls.table.body.after') !!}
                </x-admin::table>
            </div>

            {!! view_render_event('admin.leads.create.products.form_controls.table.after') !!}

            <!-- Add New Product Item -->
            <button
                type="button"
                class="flex max-w-max items-center gap-2 text-brandColor"
                @click="addProduct"
            >
                <i class="icon-add text-md !text-brandColor"></i>

                @lang('admin::app.leads.common.products.add-more')
            </button>

        </div>
    </script>

    <script
        type="text/x-template"
        id="v-product-item-template"
    >
        <x-admin::table.thead.tr>
            <!-- Product Name -->
            <x-admin::table.td>
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::lookup
                        ::key="product.product_id || 'new-product'"
                        ::src="src"
                        ::name="`${inputName}[lookup_id]`"
                        :preload="true"
                        :can-add-new="$canCreateProduct"
                        :defer-add-new="true"
                        ::value="{ id: product.product_id, name: product.name }"
                        :placeholder="trans('admin::app.leads.common.products.product-name')"
                        @on-selected="(result) => addProduct(result)"
                        @add-new="openProductModal"
                    />

                    <input
                        type="hidden"
                        :name="`${inputName}[name]`"
                        :value="product.name"
                    />

                    <input
                        v-if="product.product_id"
                        type="hidden"
                        :name="`${inputName}[product_id]`"
                        :value="product.product_id"
                    />

                    <x-admin::form.control-group.error ::name="`${inputName}[product_id]`" />
                </x-admin::form.control-group>

                <x-admin::modal
                    ref="productModal"
                    size="medium"
                    @toggle="syncProductModal"
                >
                    <x-slot:header>
                        <p class="text-lg font-bold text-gray-800 dark:text-white">
                            @lang('admin::app.products.create.title')
                        </p>
                    </x-slot:header>

                    <x-slot:content>
                        <div
                            v-if="productModalOpen"
                            class="grid max-h-[70vh] grid-cols-1 gap-4 overflow-y-auto sm:grid-cols-2"
                            @keydown.enter.prevent
                        >
                            <p
                                v-if="productError"
                                class="text-sm text-red-600 sm:col-span-2"
                            >
                                @{{ productError }}
                            </p>

                            <div class="sm:col-span-2">
                                <label class="required mb-1 block text-sm font-medium text-gray-800 dark:text-white">
                                    @lang('admin::app.leads.common.products.product-name')
                                </label>

                                <input
                                    type="text"
                                    class="w-full rounded border border-gray-300 px-2.5 py-2 text-sm text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                                    v-model="productDraft.name"
                                    autocomplete="off"
                                />
                            </div>

                            <div>
                                <label class="required mb-1 block text-sm font-medium text-gray-800 dark:text-white">
                                    @lang('admin::app.products.view.sku')
                                </label>

                                <input
                                    type="text"
                                    class="w-full rounded border border-gray-300 px-2.5 py-2 text-sm text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                                    v-model="productDraft.sku"
                                    autocomplete="off"
                                />
                            </div>

                            <div>
                                <label class="required mb-1 block text-sm font-medium text-gray-800 dark:text-white">
                                    @lang('admin::app.leads.common.products.quantity')
                                </label>

                                <input
                                    type="number"
                                    min="0"
                                    step="1"
                                    class="w-full rounded border border-gray-300 px-2.5 py-2 text-sm text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                                    v-model="productDraft.quantity"
                                />
                            </div>

                            <div>
                                <label class="required mb-1 block text-sm font-medium text-gray-800 dark:text-white">
                                    @lang('admin::app.leads.common.products.price')
                                </label>

                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full rounded border border-gray-300 px-2.5 py-2 text-sm text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                                    v-model="productDraft.price"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-gray-800 dark:text-white">
                                    @lang('installer::app.seeders.attributes.products.description')
                                </label>

                                <textarea
                                    class="w-full rounded border border-gray-300 px-2.5 py-2 text-sm text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                                    rows="3"
                                    v-model="productDraft.description"
                                ></textarea>
                            </div>
                        </div>
                    </x-slot:content>

                    <x-slot:footer>
                        <button
                            type="button"
                            class="transparent-button"
                            @click="closeProductModal"
                        >
                            @lang('admin::app.leads.view.persons.cancel-btn')
                        </button>

                        <button
                            type="button"
                            class="primary-button"
                            :disabled="productSaving"
                            @click="saveProduct"
                        >
                            @lang('admin::app.products.create.save-btn')
                        </button>
                    </x-slot:footer>
                </x-admin::modal>
            </x-admin::table.td>

            <!-- Product Quantity -->
            <x-admin::table.td class="text-right">
                <x-admin::form.control-group>
                    <x-admin::form.control-group.control
                        type="inline"
                        ::name="`${inputName}[quantity]`"
                        ::value="product.quantity"
                        rules="required|decimal:4"
                        :label="trans('admin::app.leads.common.products.quantity')"
                        :placeholder="trans('admin::app.leads.common.products.quantity')"
                        @on-change="(event) => product.quantity = event.value"
                        position="center"
                    />
                </x-admin::form.control-group>
            </x-admin::table.td>

            <!-- Price -->
            <x-admin::table.td class="text-right">
                <x-admin::form.control-group>
                    <x-admin::form.control-group.control
                        type="inline"
                        ::name="`${inputName}[price]`"
                        ::value="product.price"
                        rules="required|decimal:4"
                        :label="trans('admin::app.leads.common.products.price')"
                        :placeholder="trans('admin::app.leads.common.products.price')"
                        @on-change="(event) => product.price = event.value"
                        ::value-label="$admin.formatPrice(Number(product.price) || 0)"
                        position="center"
                    />
                </x-admin::form.control-group>
            </x-admin::table.td>

            <!-- Amount -->
            <x-admin::table.td class="text-right">
                <x-admin::form.control-group>
                    <x-admin::form.control-group.control
                        type="inline"
                        ::name="`${inputName}[amount]`"
                        ::value="product.price * product.quantity"
                        rules="required|decimal:4"
                        :label="trans('admin::app.leads.common.products.total')"
                        :placeholder="trans('admin::app.leads.common.products.total')"
                        ::value-label="$admin.formatPrice((Number(product.price) || 0) * (Number(product.quantity) || 0))"
                        :allowEdit="false"
                        position="center"
                    />
                </x-admin::form.control-group>
            </x-admin::table.td>

            <!-- Action -->
            <x-admin::table.td class="text-right">
                <x-admin::form.control-group >
                    <i
                        @click="removeProduct"
                        class="icon-delete cursor-pointer text-2xl"
                    ></i>
                </x-admin::form.control-group>
            </x-admin::table.td>
        </x-admin::table.thead.tr>
    </script>

    <script type="module">
        app.component('v-product-list', {
            template: '#v-product-list-template',

            props: {
                data: {
                    type: Array,
                    default: null,
                },
                canCreateProduct: {
                    type: Boolean,
                    default: false,
                },
            },

            data: function () {
                return {
                    products: this.data ? this.data : [],
                }
            },


            created() {
                if (! this.data) {
                    this.addProduct();
                }
            },

            methods: {
                addProduct() {
                    this.products.push({
                        id: null,
                        product_id: null,
                        name: '',
                        quantity: 0,
                        price: 0,
                        amount: null,
                    })
                },

                removeProduct (product) {
                    const index = this.products.indexOf(product);
                    this.products.splice(index, 1);
                },
            },
        });

        app.component('v-product-item', {
            template: '#v-product-item-template',

            props: ['index', 'product', 'canCreateProduct'],

            data() {
                return {
                    products: [],

                    productModalOpen: false,

                    productSaving: false,

                    productError: '',

                    productDraft: {
                        name: '',
                        sku: '',
                        description: '',
                        quantity: 1,
                        price: '',
                    },
                }
            },

            computed: {
                inputName() {
                    if (this.product.id) {
                        return "products[" + this.product.id + "]";
                    }

                    return "products[product_" + this.index + "]";
                },

                src() {
                    return "{{ route('admin.products.search') }}";
                },

                params() {
                    return {
                        params: {
                            query: this.product.name,
                        },
                    };
                },
            },

            methods: {
                /**
                 * Add the product.
                 *
                 * @param {Object} result
                 *
                 * @return {void}
                 */
                addProduct(result) {
                    this.product.product_id = result.id || null;

                    this.product.name = result.name || '';

                    this.product.price = result.price ?? this.product.price ?? 0;

                    this.product.quantity = result.quantity ?? (this.product.quantity || 1);
                },

                openProductModal(name) {
                    this.productError = '';
                    this.productDraft = {
                        name: name || '',
                        sku: this.suggestSku(name),
                        description: '',
                        quantity: 1,
                        price: '',
                    };

                    this.productModalOpen = true;
                    this.$nextTick(() => this.$refs.productModal.open());
                },

                closeProductModal() {
                    this.productModalOpen = false;
                    this.$refs.productModal.close();
                },

                syncProductModal(state) {
                    this.productModalOpen = !! state?.isActive;
                },

                suggestSku(name) {
                    const base = String(name || 'product')
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '')
                        .slice(0, 20) || 'product';

                    return base + '-' + Math.random().toString(36).slice(2, 8);
                },

                saveProduct() {
                    const name = String(this.productDraft.name || '').trim();
                    const sku = String(this.productDraft.sku || '').trim() || this.suggestSku(name);
                    const quantity = this.productDraft.quantity;
                    const price = this.productDraft.price;

                    if (! name || ! sku || quantity === '' || quantity === null || price === '' || price === null) {
                        this.productError = @json(__('validation.required', ['attribute' => __('admin::app.leads.common.products.product-name')]));

                        return;
                    }

                    this.productSaving = true;
                    this.productError = '';

                    this.$axios.post("{{ route('admin.products.store') }}", {
                            entity_type: 'products',
                            name,
                            sku,
                            description: String(this.productDraft.description || '').trim(),
                            quantity,
                            price,
                        })
                        .then((response) => {
                            const created = response.data.data || {};
                            const lineQuantity = this.product.quantity || 1;

                            this.addProduct({
                                id: created.id,
                                name: created.name || name,
                                price: created.price ?? price,
                                quantity: lineQuantity,
                            });

                            this.closeProductModal();

                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data.message,
                            });
                        })
                        .catch((error) => {
                            const errors = error?.response?.data?.errors;
                            const first = errors ? Object.values(errors)[0] : null;

                            this.productError = Array.isArray(first)
                                ? first[0]
                                : (error?.response?.data?.message || '');
                        })
                        .finally(() => {
                            this.productSaving = false;
                        });
                },

                /**
                 * Remove the product.
                 *
                 * @return {void}
                 */
                removeProduct () {
                    this.$emit('onRemoveProduct', this.product)
                }
            }
        });
    </script>
@endPushOnce