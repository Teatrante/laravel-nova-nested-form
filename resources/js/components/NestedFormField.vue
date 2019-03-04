<template>
  <div class="nested-form">
    <!-- HEADING -->
    <div class="p-4 border-b border-40 bg-30 flex justify-between items-center"
         :key="`${field.attribute}-${index}`">
      <h1 class="text-90 font-normal text-xl">{{heading}}</h1>
      <div class="flex justify-between items-center">

        <div>
            <a href="javascript:;" @click="reorderSwapResource(index - 1)" class="cursor-pointer text-70 hover:text-primary">
                <svg  xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" aria-labelledby="up" role="presentation" class="fill-current">
                    <path class="heroicon-ui" d="M13 5.41V21a1 1 0 0 1-2 0V5.41l-5.3 5.3a1 1 0 1 1-1.4-1.42l7-7a1 1 0 0 1 1.4 0l7 7a1 1 0 1 1-1.4 1.42L13 5.4z"/>
                </svg>
            </a>
            <a href="javascript:;" @click="reorderSwapResource(index + 1)" class="cursor-pointer text-70 hover:text-primary mr-3 ">
                <svg  xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" aria-labelledby="down" role="presentation" class="fill-current">
                    <path class="heroicon-ui" d="M11 18.59V3a1 1 0 0 1 2 0v15.59l5.3-5.3a1 1 0 0 1 1.4 1.42l-7 7a1 1 0 0 1-1.4 0l-7-7a1 1 0 0 1 1.4-1.42l5.3 5.3z"/>
                </svg>
            </a>
        </div>

        <div @click="toggleVisibility"
             class="appearance-none cursor-pointer text-70 hover:text-primary mr-3 items-center flex">
          <icon type="view"
                width="22"
                height="18"
                view-box="0 0 22 16" />
        </div>

        <div @click="showDeleteModal = true"
             class="appearance-none cursor-pointer text-70 hover:text-danger mr-3 items-center flex">
          <icon type="delete" />
        </div>
      </div>
    </div>
    <!-- HEADING -->
    <div v-show="child.opened">

      <!-- ERROR ON ATTACHED RESOURCE -->
      <help-text class="error-text text-danger text-center m-4"
                 v-if="hasError">
        {{ firstError }}
      </help-text>
      <!-- ERROR ON ATTACHED RESOURCE -->

      <!-- ACTUAL FIELDS -->
      <component v-for="subfield in child.fields"
                 :field="subfield"
                 :key="subfield.attribute"
                 :errors="errors"
                 :resource-id="child[field.ID]"
                 :resource-name="field.resourceName"
                 :via-resource="field.viaResource"
                 :via-resource-id="field.viaResourceId"
                 :via-relationship="field.viaRelationship"
                 :related-resource-name="field.relatedResourceName"
                 :related-resource-id="field.relatedResourceId"
                 @file-deleted="$emit('file-deleted')"
                 :is="`form-${getComponent(subfield)}`" />
      <!-- ACTUAL FIELDS -->

    </div>

    <!-- DELETION MODAL -->
    <DeleteModal :resourceSingularName="field.singularLabel"
                 @submit="remove"
                 @close="showDeleteModal = false"
                 v-if="showDeleteModal" />
    <!-- DELETION MODAL -->

  </div>
</template>

<script>
import FormNestedBelongsToField from './CustomNestedFields/BelongsToField'
import FormNestedFileField from './CustomNestedFields/FileField'
import DeleteModal from './Modals/Delete'

export default {

    components: { FormNestedBelongsToField, FormNestedFileField, DeleteModal },


    props: {
        field: {
        type: Object,
        required: true
        },
        child: {
        type: Object,
        required: true
        },
        index: {
        type: Number
        },
        errors: {
        type: Object
        }
    },


    data() {
        return {
        showDeleteModal: false
        }
    },

    computed: {
        /**
         * Transforms the heading
         */
        heading() {
            return this.child.heading.replace(/{{(.*?)}}/g, (val, key) => {
                return key === 'index' ? this.index + 1 || '' : key === 'id' ? this.field.resourceId || '' :
                (this.child.fields.find(field => field.original_attribute === key) || {}).value
            }).replace(/ - $/, '')
        },

        /**
         * Get the error attribute
         */
        errorAttribute() {
            return `${this.field.attribute}${this.field.has_many ? `[${this.index}]` : ''}`
        },

        /**
         * Checks whether the field has errors
         */
        hasError() {
            return Object.keys(this.errors.errors).includes(this.errorAttribute)
        },

        /**
         * Get the first error
         */
        firstError() {
            return this.errors.errors[this.errorAttribute][0]
        }
    },

    methods: {
        /**
         * This toggles the visibility of the
         * content of the related resource
         */
        toggleVisibility() {
            this.child.opened = !this.child.opened
        },

        /**
         * This removes the current child from the
         * parent.
         */
        remove() {
            this.field.children.splice(this.index, 1)
        },

        /**
         * Fill the formData with the children.
         */
        fill(formData) {
            this.child.fields.forEach(field => field.fill(formData))

            if (this.child[this.field.ID]) {
                formData.append(`${this.field.attribute}[${this.index}][${this.field.ID}]`, this.child[this.field.ID])
            }
        },

        /**
         * Get the component dependind on the field.
         */
        getComponent(field) {

            if (['belongs-to-field', 'file-field'].includes(field.component)) {
                return 'nested-' + field.component
            }

            return field.component

        },

        async reorderSwapResource($swapResourceIndex) {

            if( this.field.children[$swapResourceIndex] === undefined ){
                this.$toasted.show(
                    this.__('Undefined target resource.'),
                    {type: 'error'}
                );
            } else {

                try {
                    const response = await this.reorderSwapRequest(this.field.children[$swapResourceIndex][this.field.ID]);

                    this.$toasted.show(
                        this.__('The new order has been set!'),
                        {type: 'success'}
                    );

                    this.$router.go(this.$router.currentRoute);
                    
                } catch (error) {
                    this.$toasted.show(
                        this.__('An error occurred while trying to reorder the resource.'),
                        {type: 'error'}
                    );
                }
            }

        },


        /**
         * Reorder Resource by swap
         */
        reorderSwapRequest($swapModelId) {
            return Nova.request().patch(
                `/nova-vendor/naxon/nova-field-sortable/${this.field.resourceName}/${this.child[this.field.ID]}/reorder`,
                {
                    swapModelId: $swapModelId
                }
            );
        },

    },

    watch: {
        /**
         * Watches for errors in sub fields.
         */
        errors({ errors }) {
            for (let attribute in errors) {
                if (attribute.includes(this.field.attribute)) {
                this.child.opened = true
                break
                }
            }
        }
    },
    created() {
        this.child.fill = this.fill
    }
}
</script>
