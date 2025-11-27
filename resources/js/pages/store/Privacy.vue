<template>
    <StoreLayout>
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-3xl md:text-4xl font-bold mb-6">Политика конфиденциальности</h1>
                
                <div class="prose max-w-none space-y-6">
                    <section>
                        <p class="text-muted-foreground">
                            <strong>Дата вступления в силу:</strong> {{ currentDate }}
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold mb-4">1. Общие положения</h2>
                        <p class="text-muted-foreground mb-4">
                            Настоящая Политика конфиденциальности определяет порядок обработки и защиты персональных данных 
                            пользователей интернет-магазина Essens (далее — «Сайт»).
                        </p>
                        <p class="text-muted-foreground">
                            Используя Сайт, вы соглашаетесь с условиями настоящей Политики конфиденциальности.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold mb-4">2. Собираемая информация</h2>
                        <p class="text-muted-foreground mb-4">
                            Мы собираем следующую информацию:
                        </p>
                        <ul class="list-disc list-inside space-y-2 text-muted-foreground ml-4">
                            <li>Имя и контактная информация (email, телефон)</li>
                            <li>Адрес доставки</li>
                            <li>Информация о заказах</li>
                            <li>Данные о посещении сайта (cookies, IP-адрес)</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold mb-4">3. Использование информации</h2>
                        <p class="text-muted-foreground mb-4">
                            Собранная информация используется для:
                        </p>
                        <ul class="list-disc list-inside space-y-2 text-muted-foreground ml-4">
                            <li>Обработки и выполнения заказов</li>
                            <li>Связи с клиентами</li>
                            <li>Улучшения качества обслуживания</li>
                            <li>Отправки информационных сообщений (с вашего согласия)</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold mb-4">4. Защита данных</h2>
                        <p class="text-muted-foreground">
                            Мы применяем современные методы защиты информации для обеспечения безопасности 
                            ваших персональных данных от несанкционированного доступа, изменения, раскрытия или уничтожения.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold mb-4">5. Передача данных третьим лицам</h2>
                        <p class="text-muted-foreground">
                            Мы не продаем, не обмениваем и не передаем ваши персональные данные третьим лицам, 
                            за исключением случаев, когда это необходимо для выполнения заказа (например, службы доставки) 
                            или требуется по закону.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold mb-4">6. Ваши права</h2>
                        <p class="text-muted-foreground mb-4">
                            Вы имеете право:
                        </p>
                        <ul class="list-disc list-inside space-y-2 text-muted-foreground ml-4">
                            <li>Получать информацию о ваших персональных данных</li>
                            <li>Требовать исправления неточных данных</li>
                            <li>Требовать удаления ваших данных</li>
                            <li>Отозвать согласие на обработку данных</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold mb-4">7. Контакты</h2>
                        <p class="text-muted-foreground">
                            По вопросам, связанным с обработкой персональных данных, обращайтесь по email: 
                            <a href="mailto:privacy@essens.ru" class="text-primary hover:underline">privacy@essens.ru</a>
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </StoreLayout>
</template>

<script>
import { computed, onMounted } from 'vue';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { setMetaTags, getBaseUrl } from '@/utils/seo';

export default {
    name: 'Privacy',
    components: {
        StoreLayout,
    },
    setup() {
        const currentDate = computed(() => {
            return new Date().toLocaleDateString('ru-RU', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            });
        });

        onMounted(() => {
            const baseUrl = getBaseUrl();
            setMetaTags({
                title: 'Политика конфиденциальности — Essens',
                description: 'Политика конфиденциальности интернет-магазина Essens. Информация о сборе, использовании и защите персональных данных.',
                keywords: 'политика конфиденциальности, защита данных, Essens',
                url: `${baseUrl}/privacy`,
                type: 'website',
            });
        });

        return {
            currentDate,
        };
    },
};
</script>


