<template>
    <input :name="input_name" type="hidden" :value="selected">
    <v-autocomplete
        clearable
        :disabled="disabled"
        :label="label"
        :items="items"
        :item-title="option_title"
        :item-value="option_value"
        variant="outlined"
        type="input"
        density="compact"
        height="100"
        @input="updateSelect($event)"
        v-model="selected"
        :loading="loading"
    >
    </v-autocomplete>
</template>
<script>
import { VAutocomplete } from 'vuetify/components/VAutocomplete';
import 'vuetify/lib/components/VAutocomplete/VAutocomplete.css';
import axios from "axios";

export default {
    name: "DynamicSelect",
    props:[
        'default_selected',
        'input_name',
        'label',
        'url',
        'option_title',
        'option_value',
        'disabled'
    ],
    components:{
        VAutocomplete
    },
    watch:{
        default_selected: {
            handler: function (value) {
                console.log(value, "def")
                if(value) this.selected = Number(value)
            },
            immediate: true
        },
    },
    created(){
        this.loading =true;
        console.log(this.url)
        axios.get(this.url)
            .then(res => {
                this.items = res.data;
                this.loading =false;
            })
            .catch()
    },
    methods: {
        updateSelect($event){
            axios.get(this.url, {params:
                    {key:$event.target.value}
            }).then(res => {
                    this.items = res.data;
                })
                .catch()
        }
    },
    data() {
        return {
            items:[],
            names: [],
            values: [],
            selected:null,
            loading:  false
        }
    }
};
</script>
<style lang="scss">
.v-input__control{
    height: 38px;
}
</style>
