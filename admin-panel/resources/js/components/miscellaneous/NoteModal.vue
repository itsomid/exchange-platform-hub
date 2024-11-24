<template >
    <div class="modal fade " :id="id" tabindex="-1" :aria-labelledby="id+'label'" aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" :id="id+'label'">یادداشت پشتیبان</h5>


                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    <div v-if="completed" class="alert alert-success" role="alert">
                        یادداشت با موفقیت ذخیره شد.
                    </div>
                    <form>
                        <div class="mb-3">
                            <label for="message-text" class="col-form-label">یادداشت:</label>
                            <textarea class="form-control" id="message-text" v-model="noteText"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button"  id="close"  class="btn btn btn-outline-secondary" data-bs-dismiss="modal">بستن</button>
                    <button type="button" class="btn btn-primary text-white" :class="{'button-loading':loading}" @click="sendNote()">
                        <span class="button_text">ارسال یادداشت</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>

import axios from "axios";

export default {
    name: "NoteModal",
    props:[
        'id',
        'url',
        'support_description'
    ],
    data() {
        return {
            noteText: '',
            loading:false,
            completed: false
        }
    },
    mounted() {
        this.noteText = this.support_description
    },
    methods: {
        sendNote() {
            this.loading = true
            axios.patch(this.url, {
                support_description: this.noteText
            }).then(res => {
                this.loading = false
                this.completed = true
                setTimeout(()=>{
                    this.completed = false
                },2000)
            }).catch()
        }
    }
}
</script>

<style scoped>

.button-loading .button_text {
    visibility: hidden;
    opacity: 0;
}

.button-loading::after {
    content: "";
    position: absolute;
    width: 16px;
    height: 16px;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    margin: auto;
    border: 4px solid transparent;
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: button-loading-spinner 1s ease infinite;
}

@keyframes button-loading-spinner {
    from {
        transform: rotate(0turn);
    }

    to {
        transform: rotate(1turn);
    }
}
</style>
