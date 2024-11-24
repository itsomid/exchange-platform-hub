<template>
    <file-pond
        name="filepond[]"
        v-model="file"
        ref="ponds"
        label-idle='جهت درج تصویر <span class="filepond--label-action">اینجا</span> کلیک کنید'
        v-bind:allow-multiple="false"
        accepted-file-types="image/jpeg,image/jpg,image/png"
        :server="filePondServerConfig"
    />
</template>

<script>
import vueFilePond from 'vue-filepond';
import "filepond/dist/filepond.min.css";
import "filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css";
import FilePondPluginFileValidateType from "filepond-plugin-file-validate-type";
import FilePondPluginImagePreview from "filepond-plugin-image-preview";
import FilePondPluginImageResize from 'filepond-plugin-image-resize';
import FilePondPluginImageTransform from 'filepond-plugin-image-transform';

vueFilePond(FilePondPluginImagePreview, FilePondPluginFileValidateType, FilePondPluginImageResize, FilePondPluginImageTransform);
const FilePond = vueFilePond()
export default {

    name: "Filepond",
    props: {
        storeAddress: String
    },
    components: {
        FilePond
    },
    data(){
        return{
            file: [],
            filePondServerConfig: {
                process: {
                    url:`https://store.bitexroom.com/index.php?objective=${this.storeAddress}`,
                    method: 'POST',
                    onload: (response) => {
                        response = JSON.parse(response);
                        return response[0].key;
                    },
                    onerror: (response) => {
                        response = JSON.parse(response);

                        return response.msg
                    },
                    ondata: (formData) => {
                        window.h = formData;
                        return formData;
                    }
                },
            },
        }
    }
}
</script>

<style scoped>

</style>
