<template>
    <StoreLayout>
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-3xl md:text-4xl font-bold mb-6">Контакты</h1>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
                    <!-- Contact Information -->
                    <div>
                        <h2 class="text-2xl font-semibold mb-6">Свяжитесь с нами</h2>
                        <div class="space-y-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-primary mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <div>
                                    <p class="font-medium">Email</p>
                                    <a href="mailto:info@essens.ru" class="text-primary hover:underline">info@essens.ru</a>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-primary mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <div>
                                    <p class="font-medium">Телефон</p>
                                    <a href="tel:+79991234567" class="text-primary hover:underline">+7 (999) 123-45-67</a>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-primary mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <div>
                                    <p class="font-medium">Адрес</p>
                                    <p class="text-muted-foreground">г. Москва, ул. Примерная, д. 1</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-primary mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <p class="font-medium">Время работы</p>
                                    <p class="text-muted-foreground">Пн-Пт: 9:00 - 20:00<br>Сб-Вс: 10:00 - 18:00</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Form -->
                    <div class="bg-card rounded-lg border border-border p-6">
                        <h2 class="text-2xl font-semibold mb-6">Напишите нам</h2>
                        <form @submit.prevent="handleSubmit" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">Имя</label>
                                <input
                                    type="text"
                                    v-model="form.name"
                                    required
                                    class="w-full bg-surface border border-border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Email</label>
                                <input
                                    type="email"
                                    v-model="form.email"
                                    required
                                    class="w-full bg-surface border border-border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Сообщение</label>
                                <textarea
                                    v-model="form.message"
                                    required
                                    rows="5"
                                    class="w-full bg-surface border border-border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary"
                                ></textarea>
                            </div>
                            <button
                                type="submit"
                                class="w-full py-3 bg-primary text-primary-contrast rounded-lg font-medium hover:opacity-90 transition-opacity"
                            >
                                Отправить сообщение
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </StoreLayout>
</template>

<script>
import { ref, onMounted } from 'vue';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { setMetaTags, addStructuredData, getBaseUrl } from '@/utils/seo';

export default {
    name: 'Contacts',
    components: {
        StoreLayout,
    },
    setup() {
        const form = ref({
            name: '',
            email: '',
            message: '',
        });

        const handleSubmit = () => {
            // Логика отправки формы будет реализована позже
            alert('Спасибо за ваше сообщение! Мы свяжемся с вами в ближайшее время.');
            form.value = {
                name: '',
                email: '',
                message: '',
            };
        };

        onMounted(() => {
            const baseUrl = getBaseUrl();
            setMetaTags({
                title: 'Контакты Essens — Свяжитесь с нами',
                description: 'Свяжитесь с интернет-магазином Essens. Телефон, email, адрес. Мы всегда готовы ответить на ваши вопросы.',
                keywords: 'контакты Essens, связь, поддержка, телефон, email',
                url: `${baseUrl}/contacts`,
                type: 'website',
            });
        });

        return {
            form,
            handleSubmit,
        };
    },
};
</script>


