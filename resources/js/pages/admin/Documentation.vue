<template>
    <div class="documentation-page">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-semibold text-foreground">Документация</h1>
                <p class="text-muted-foreground mt-1">Полное руководство по работе с системой управления товарами</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Навигация -->
            <div class="lg:col-span-1">
                <div class="bg-card rounded-lg border border-border p-4 sticky top-4">
                    <h2 class="text-lg font-semibold mb-4">Содержание</h2>
                    <nav class="space-y-2">
                        <a
                            v-for="section in sections"
                            :key="section.id"
                            @click="scrollToSection(section.id)"
                            :class="[
                                'block px-3 py-2 rounded-lg text-sm transition-colors cursor-pointer',
                                activeSection === section.id
                                    ? 'bg-primary text-primary-contrast'
                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                            ]"
                        >
                            {{ section.title }}
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Контент -->
            <div class="lg:col-span-3">
                <div class="space-y-8">
                    <!-- Категории -->
                    <section :id="sections[0].id" class="scroll-mt-4">
                        <div class="bg-card rounded-lg border border-border p-6">
                            <h2 class="text-2xl font-semibold mb-4 flex items-center gap-2">
                                <span class="text-3xl">📁</span>
                                {{ sections[0].title }}
                            </h2>
                            
                            <div class="prose prose-invert max-w-none space-y-6">
                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Описание</h3>
                                    <p class="text-muted-foreground">
                                        Категории товаров используются для организации и структурирования каталога. 
                                        Каждая категория может содержать несколько подкатегорий и товаров.
                                    </p>
                                </div>

                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Создание категории</h3>
                                    <ol class="list-decimal list-inside space-y-2 text-muted-foreground">
                                        <li>Перейдите в раздел <strong class="text-foreground">Товары → Категории</strong></li>
                                        <li>Нажмите кнопку <strong class="text-foreground">"Создать категорию"</strong></li>
                                        <li>Заполните обязательные поля:
                                            <ul class="list-disc list-inside ml-4 mt-2 space-y-1">
                                                <li><strong class="text-foreground">Название</strong> - название категории (например: "Косметика")</li>
                                                <li><strong class="text-foreground">Slug</strong> - URL-адрес категории (автоматически генерируется из названия)</li>
                                            </ul>
                                        </li>
                                        <li>Настройте дополнительные параметры:
                                            <ul class="list-disc list-inside ml-4 mt-2 space-y-1">
                                                <li><strong class="text-foreground">Активность</strong> - включите/выключите отображение категории на сайте</li>
                                                <li><strong class="text-foreground">Позиция</strong> - порядок отображения категории (можно изменить перетаскиванием)</li>
                                            </ul>
                                        </li>
                                        <li>Нажмите <strong class="text-foreground">"Сохранить"</strong></li>
                                    </ol>
                                </div>

                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Редактирование категории</h3>
                                    <ol class="list-decimal list-inside space-y-2 text-muted-foreground">
                                        <li>В списке категорий найдите нужную категорию</li>
                                        <li>Нажмите кнопку <strong class="text-foreground">"Редактировать"</strong></li>
                                        <li>Внесите необходимые изменения</li>
                                        <li>Нажмите <strong class="text-foreground">"Сохранить"</strong></li>
                                    </ol>
                                </div>

                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Удаление категории</h3>
                                    <ol class="list-decimal list-inside space-y-2 text-muted-foreground">
                                        <li>В списке категорий найдите нужную категорию</li>
                                        <li>Нажмите кнопку <strong class="text-foreground">"Удалить"</strong></li>
                                        <li>Подтвердите удаление в диалоговом окне</li>
                                    </ol>
                                    <div class="mt-3 p-4 bg-yellow-500/10 border border-yellow-500/20 rounded-lg">
                                        <p class="text-sm text-yellow-600 dark:text-yellow-400">
                                            <strong>⚠️ Внимание:</strong> При удалении категории все связанные подкатегории и товары также будут удалены. 
                                            Убедитесь, что вы хотите удалить категорию перед подтверждением.
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Изменение порядка категорий</h3>
                                    <p class="text-muted-foreground mb-3">
                                        Для изменения порядка отображения категорий используйте функцию перетаскивания:
                                    </p>
                                    <ol class="list-decimal list-inside space-y-2 text-muted-foreground">
                                        <li>Наведите курсор на строку с категорией</li>
                                        <li>Зажмите левую кнопку мыши и перетащите категорию в нужное место</li>
                                        <li>Отпустите кнопку мыши - порядок автоматически сохранится</li>
                                    </ol>
                                </div>

                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Связи</h3>
                                    <ul class="list-disc list-inside space-y-2 text-muted-foreground">
                                        <li><strong class="text-foreground">Категория → Подкатегории</strong> - одна категория может содержать множество подкатегорий</li>
                                        <li><strong class="text-foreground">Категория → Товары</strong> - товары связаны с категорией через подкатегории</li>
                                    </ul>
                                </div>

                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Примеры</h3>
                                    <div class="bg-muted/30 rounded-lg p-4 mt-3">
                                        <p class="text-sm font-mono text-foreground mb-2">Пример структуры категорий:</p>
                                        <pre class="text-xs text-muted-foreground overflow-x-auto">Косметика (категория)
  ├── Уход за лицом (подкатегория)
  │   ├── Кремы
  │   ├── Сыворотки
  │   └── Маски
  └── Уход за телом (подкатегория)
      ├── Лосьоны
      └── Скрабы</pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Подкатегории -->
                    <section :id="sections[1].id" class="scroll-mt-4">
                        <div class="bg-card rounded-lg border border-border p-6">
                            <h2 class="text-2xl font-semibold mb-4 flex items-center gap-2">
                                <span class="text-3xl">📂</span>
                                {{ sections[1].title }}
                            </h2>
                            
                            <div class="prose prose-invert max-w-none space-y-6">
                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Описание</h3>
                                    <p class="text-muted-foreground">
                                        Подкатегории позволяют более детально структурировать товары внутри категорий. 
                                        Каждая подкатегория принадлежит одной категории и может содержать множество товаров.
                                    </p>
                                </div>

                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Создание подкатегории</h3>
                                    <ol class="list-decimal list-inside space-y-2 text-muted-foreground">
                                        <li>Перейдите в раздел <strong class="text-foreground">Товары → Подкатегории</strong></li>
                                        <li>Нажмите кнопку <strong class="text-foreground">"Создать подкатегорию"</strong></li>
                                        <li>Заполните обязательные поля:
                                            <ul class="list-disc list-inside ml-4 mt-2 space-y-1">
                                                <li><strong class="text-foreground">Категория</strong> - выберите родительскую категорию из списка</li>
                                                <li><strong class="text-foreground">Название</strong> - название подкатегории (например: "Кремы для лица")</li>
                                                <li><strong class="text-foreground">Slug</strong> - URL-адрес подкатегории (автоматически генерируется из названия)</li>
                                            </ul>
                                        </li>
                                        <li>Настройте дополнительные параметры:
                                            <ul class="list-disc list-inside ml-4 mt-2 space-y-1">
                                                <li><strong class="text-foreground">Активность</strong> - включите/выключите отображение подкатегории на сайте</li>
                                                <li><strong class="text-foreground">Позиция</strong> - порядок отображения подкатегории (можно изменить перетаскиванием)</li>
                                            </ul>
                                        </li>
                                        <li>Нажмите <strong class="text-foreground">"Сохранить"</strong></li>
                                    </ol>
                                </div>

                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Редактирование подкатегории</h3>
                                    <ol class="list-decimal list-inside space-y-2 text-muted-foreground">
                                        <li>В списке подкатегорий найдите нужную подкатегорию</li>
                                        <li>Нажмите кнопку <strong class="text-foreground">"Редактировать"</strong></li>
                                        <li>Внесите необходимые изменения</li>
                                        <li>Нажмите <strong class="text-foreground">"Сохранить"</strong></li>
                                    </ol>
                                </div>

                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Удаление подкатегории</h3>
                                    <ol class="list-decimal list-inside space-y-2 text-muted-foreground">
                                        <li>В списке подкатегорий найдите нужную подкатегорию</li>
                                        <li>Нажмите кнопку <strong class="text-foreground">"Удалить"</strong></li>
                                        <li>Подтвердите удаление в диалоговом окне</li>
                                    </ol>
                                    <div class="mt-3 p-4 bg-yellow-500/10 border border-yellow-500/20 rounded-lg">
                                        <p class="text-sm text-yellow-600 dark:text-yellow-400">
                                            <strong>⚠️ Внимание:</strong> При удалении подкатегории все связанные товары также будут удалены. 
                                            Убедитесь, что вы хотите удалить подкатегорию перед подтверждением.
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Изменение порядка подкатегорий</h3>
                                    <p class="text-muted-foreground mb-3">
                                        Для изменения порядка отображения подкатегорий используйте функцию перетаскивания:
                                    </p>
                                    <ol class="list-decimal list-inside space-y-2 text-muted-foreground">
                                        <li>Наведите курсор на строку с подкатегорией</li>
                                        <li>Зажмите левую кнопку мыши и перетащите подкатегорию в нужное место</li>
                                        <li>Отпустите кнопку мыши - порядок автоматически сохранится</li>
                                    </ol>
                                </div>

                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Связи</h3>
                                    <ul class="list-disc list-inside space-y-2 text-muted-foreground">
                                        <li><strong class="text-foreground">Подкатегория → Категория</strong> - каждая подкатегория принадлежит одной категории (связь many-to-one)</li>
                                        <li><strong class="text-foreground">Подкатегория → Товары</strong> - одна подкатегория может содержать множество товаров (связь one-to-many)</li>
                                    </ul>
                                </div>

                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Примеры</h3>
                                    <div class="bg-muted/30 rounded-lg p-4 mt-3">
                                        <p class="text-sm font-mono text-foreground mb-2">Пример создания подкатегории:</p>
                                        <div class="text-xs text-muted-foreground space-y-2">
                                            <p><strong class="text-foreground">Категория:</strong> Косметика</p>
                                            <p><strong class="text-foreground">Название:</strong> Кремы для лица</p>
                                            <p><strong class="text-foreground">Slug:</strong> kremy-dlya-litsa</p>
                                            <p><strong class="text-foreground">Активность:</strong> ✓ Включена</p>
                                            <p><strong class="text-foreground">Позиция:</strong> 1</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Товары -->
                    <section :id="sections[2].id" class="scroll-mt-4">
                        <div class="bg-card rounded-lg border border-border p-6">
                            <h2 class="text-2xl font-semibold mb-4 flex items-center gap-2">
                                <span class="text-3xl">📦</span>
                                {{ sections[2].title }}
                            </h2>
                            
                            <div class="prose prose-invert max-w-none space-y-6">
                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Описание</h3>
                                    <p class="text-muted-foreground">
                                        Товары - это основной элемент каталога. Каждый товар принадлежит одной подкатегории, 
                                        может иметь несколько изображений, вариантов и участвовать в промо-акциях.
                                    </p>
                                </div>

                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Создание товара</h3>
                                    <ol class="list-decimal list-inside space-y-2 text-muted-foreground">
                                        <li>Перейдите в раздел <strong class="text-foreground">Товары → Все товары</strong></li>
                                        <li>Нажмите кнопку <strong class="text-foreground">"Создать товар"</strong></li>
                                        <li>Заполните обязательные поля:
                                            <ul class="list-disc list-inside ml-4 mt-2 space-y-1">
                                                <li><strong class="text-foreground">Подкатегория</strong> - выберите подкатегорию из списка (обязательно)</li>
                                                <li><strong class="text-foreground">Название</strong> - название товара (обязательно)</li>
                                                <li><strong class="text-foreground">Цена</strong> - цена товара в рублях (обязательно)</li>
                                            </ul>
                                        </li>
                                        <li>Заполните дополнительные поля:
                                            <ul class="list-disc list-inside ml-4 mt-2 space-y-1">
                                                <li><strong class="text-foreground">SKU</strong> - артикул товара (уникальный идентификатор)</li>
                                                <li><strong class="text-foreground">Тип</strong> - тип товара (например: "cream", "serum")</li>
                                                <li><strong class="text-foreground">Целевой пол</strong> - для кого предназначен товар (мужской, женский, унисекс)</li>
                                                <li><strong class="text-foreground">Объем</strong> - объем товара (например: "100 мл")</li>
                                                <li><strong class="text-foreground">Валюта</strong> - валюта цены (RUB, USD, EUR)</li>
                                                <li><strong class="text-foreground">Количество на складе</strong> - количество единиц товара</li>
                                                <li><strong class="text-foreground">В наличии</strong> - включите, если товар есть в наличии</li>
                                                <li><strong class="text-foreground">Описание</strong> - подробное описание товара</li>
                                                <li><strong class="text-foreground">Теги</strong> - теги для поиска (через запятую)</li>
                                            </ul>
                                        </li>
                                        <li><strong class="text-foreground">Добавление изображений:</strong>
                                            <ol class="list-decimal list-inside ml-4 mt-2 space-y-1 text-muted-foreground">
                                                <li>Нажмите кнопку <strong class="text-foreground">"Выбрать изображения"</strong></li>
                                                <li>В открывшемся окне выберите изображения из медиа-библиотеки</li>
                                                <li>Вы можете выбрать несколько изображений, кликая по ним</li>
                                                <li>Нажмите <strong class="text-foreground">"Подтвердить выбор"</strong> для применения</li>
                                                <li>В списке выбранных изображений вы можете:
                                                    <ul class="list-disc list-inside ml-4 mt-1 space-y-1">
                                                        <li>Установить основное изображение (кнопка со звездочкой)</li>
                                                        <li>Изменить порядок изображений (поле "Порядок")</li>
                                                        <li>Удалить изображение (кнопка "✕")</li>
                                                    </ul>
                                                </li>
                                            </ol>
                                        </li>
                                        <li><strong class="text-foreground">Добавление вариантов товара:</strong>
                                            <ol class="list-decimal list-inside ml-4 mt-2 space-y-1 text-muted-foreground">
                                                <li>Нажмите кнопку <strong class="text-foreground">"+ Добавить вариант"</strong></li>
                                                <li>Заполните поля варианта:
                                                    <ul class="list-disc list-inside ml-4 mt-1 space-y-1">
                                                        <li><strong class="text-foreground">Название варианта</strong> - например: "50мл", "100мл"</li>
                                                        <li><strong class="text-foreground">Цена</strong> - цена для этого варианта</li>
                                                        <li><strong class="text-foreground">Количество</strong> - количество на складе</li>
                                                    </ul>
                                                </li>
                                                <li>Добавьте необходимое количество вариантов</li>
                                                <li>Удалить вариант можно кнопкой "✕"</li>
                                            </ol>
                                        </li>
                                        <li>Нажмите <strong class="text-foreground">"Сохранить"</strong></li>
                                    </ol>
                                </div>

                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Редактирование товара</h3>
                                    <ol class="list-decimal list-inside space-y-2 text-muted-foreground">
                                        <li>В списке товаров найдите нужный товар</li>
                                        <li>Нажмите кнопку <strong class="text-foreground">"Редактировать"</strong></li>
                                        <li>Откроется страница редактирования товара (не модальное окно)</li>
                                        <li>Внесите необходимые изменения:
                                            <ul class="list-disc list-inside ml-4 mt-2 space-y-1">
                                                <li>Измените поля товара</li>
                                                <li>Добавьте или удалите изображения через <strong class="text-foreground">"Выбрать изображения"</strong></li>
                                                <li>Измените варианты товара</li>
                                            </ul>
                                        </li>
                                        <li>Нажмите <strong class="text-foreground">"Сохранить"</strong></li>
                                        <li>Для возврата к списку товаров нажмите кнопку <strong class="text-foreground">"Назад"</strong> (стрелка влево)</li>
                                    </ol>
                                </div>

                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Удаление товара</h3>
                                    <ol class="list-decimal list-inside space-y-2 text-muted-foreground">
                                        <li>В списке товаров найдите нужный товар</li>
                                        <li>Нажмите кнопку <strong class="text-foreground">"Удалить"</strong></li>
                                        <li>Подтвердите удаление в диалоговом окне</li>
                                    </ol>
                                    <div class="mt-3 p-4 bg-yellow-500/10 border border-yellow-500/20 rounded-lg">
                                        <p class="text-sm text-yellow-600 dark:text-yellow-400">
                                            <strong>⚠️ Внимание:</strong> При удалении товара все связанные изображения и варианты также будут удалены. 
                                            Это действие нельзя отменить.
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Работа с изображениями товара</h3>
                                    <div class="space-y-4">
                                        <div>
                                            <h4 class="font-semibold mb-2">Выбор изображений:</h4>
                                            <ol class="list-decimal list-inside space-y-1 text-muted-foreground ml-4">
                                                <li>Нажмите <strong class="text-foreground">"Выбрать изображения"</strong> или <strong class="text-foreground">"Изменить изображения"</strong></li>
                                                <li>В модальном окне выберите изображения из медиа-библиотеки</li>
                                                <li>Кликайте по изображениям для выбора (можно выбрать несколько)</li>
                                                <li>Нажмите <strong class="text-foreground">"Подтвердить выбор"</strong> для применения</li>
                                                <li>Изображения появятся в списке выбранных</li>
                                            </ol>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold mb-2">Управление изображениями:</h4>
                                            <ul class="list-disc list-inside space-y-1 text-muted-foreground ml-4">
                                                <li><strong class="text-foreground">Основное изображение</strong> - наведите на изображение и нажмите кнопку со звездочкой (★) для установки основного</li>
                                                <li><strong class="text-foreground">Порядок</strong> - измените число в поле "Порядок" для изменения порядка отображения</li>
                                                <li><strong class="text-foreground">Удаление</strong> - наведите на изображение и нажмите кнопку "✕" для удаления</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Фильтрация и поиск товаров</h3>
                                    <p class="text-muted-foreground mb-3">
                                        В списке товаров доступны следующие фильтры:
                                    </p>
                                    <ul class="list-disc list-inside space-y-2 text-muted-foreground">
                                        <li><strong class="text-foreground">Поиск</strong> - поиск по названию, SKU или описанию товара</li>
                                        <li><strong class="text-foreground">Категория</strong> - фильтр по категории товара</li>
                                        <li><strong class="text-foreground">Подкатегория</strong> - фильтр по подкатегории товара</li>
                                        <li><strong class="text-foreground">Наличие</strong> - фильтр по наличию товара (в наличии / нет в наличии)</li>
                                        <li><strong class="text-foreground">Сортировка</strong> - сортировка по дате создания, названию или цене</li>
                                        <li><strong class="text-foreground">Порядок сортировки</strong> - по возрастанию или убыванию</li>
                                    </ul>
                                </div>

                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Связи</h3>
                                    <ul class="list-disc list-inside space-y-2 text-muted-foreground">
                                        <li><strong class="text-foreground">Товар → Подкатегория</strong> - каждый товар принадлежит одной подкатегории (связь many-to-one)</li>
                                        <li><strong class="text-foreground">Товар → Изображения</strong> - один товар может иметь множество изображений (связь one-to-many)</li>
                                        <li><strong class="text-foreground">Товар → Варианты</strong> - один товар может иметь множество вариантов (связь one-to-many)</li>
                                        <li><strong class="text-foreground">Товар → Промо-акции</strong> - товар может участвовать в нескольких промо-акциях (связь many-to-many)</li>
                                    </ul>
                                </div>

                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Примеры</h3>
                                    <div class="space-y-4">
                                        <div class="bg-muted/30 rounded-lg p-4">
                                            <p class="text-sm font-mono text-foreground mb-2">Пример создания товара:</p>
                                            <div class="text-xs text-muted-foreground space-y-1">
                                                <p><strong class="text-foreground">Подкатегория:</strong> Кремы для лица</p>
                                                <p><strong class="text-foreground">Название:</strong> Алоэ Вера Гель 100 мл</p>
                                                <p><strong class="text-foreground">SKU:</strong> m041</p>
                                                <p><strong class="text-foreground">Тип:</strong> cream</p>
                                                <p><strong class="text-foreground">Целевой пол:</strong> унисекс</p>
                                                <p><strong class="text-foreground">Объем:</strong> 100 мл</p>
                                                <p><strong class="text-foreground">Цена:</strong> 1290 RUB</p>
                                                <p><strong class="text-foreground">В наличии:</strong> ✓</p>
                                                <p><strong class="text-foreground">Количество:</strong> 50</p>
                                                <p><strong class="text-foreground">Теги:</strong> новинка, beauty, натуральный</p>
                                            </div>
                                        </div>
                                        <div class="bg-muted/30 rounded-lg p-4">
                                            <p class="text-sm font-mono text-foreground mb-2">Пример вариантов товара:</p>
                                            <div class="text-xs text-muted-foreground space-y-2">
                                                <div>
                                                    <p><strong class="text-foreground">Вариант 1:</strong></p>
                                                    <p class="ml-4">Название: 10 мл | Цена: 387 RUB | Количество: 20</p>
                                                </div>
                                                <div>
                                                    <p><strong class="text-foreground">Вариант 2:</strong></p>
                                                    <p class="ml-4">Название: 100 мл | Цена: 1290 RUB | Количество: 50</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Промо-акции -->
                    <section :id="sections[3].id" class="scroll-mt-4">
                        <div class="bg-card rounded-lg border border-border p-6">
                            <h2 class="text-2xl font-semibold mb-4 flex items-center gap-2">
                                <span class="text-3xl">🏷️</span>
                                {{ sections[3].title }}
                            </h2>
                            
                            <div class="prose prose-invert max-w-none space-y-6">
                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Описание</h3>
                                    <p class="text-muted-foreground">
                                        Промо-акции позволяют создавать специальные предложения для товаров. 
                                        Каждая промо-акция может включать несколько товаров и иметь период действия.
                                    </p>
                                </div>

                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Создание промо-акции</h3>
                                    <ol class="list-decimal list-inside space-y-2 text-muted-foreground">
                                        <li>Перейдите в раздел <strong class="text-foreground">Товары → Промо-акции</strong></li>
                                        <li>Нажмите кнопку <strong class="text-foreground">"Создать промо-акцию"</strong></li>
                                        <li>Заполните поля:
                                            <ul class="list-disc list-inside ml-4 mt-2 space-y-1">
                                                <li><strong class="text-foreground">Название</strong> - название промо-акции (обязательно)</li>
                                                <li><strong class="text-foreground">Описание</strong> - описание промо-акции (необязательно)</li>
                                                <li><strong class="text-foreground">Дата начала</strong> - дата начала действия промо-акции (необязательно)</li>
                                                <li><strong class="text-foreground">Дата окончания</strong> - дата окончания действия промо-акции (необязательно)</li>
                                            </ul>
                                        </li>
                                        <li>Нажмите <strong class="text-foreground">"Сохранить"</strong></li>
                                    </ol>
                                </div>

                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Редактирование промо-акции</h3>
                                    <ol class="list-decimal list-inside space-y-2 text-muted-foreground">
                                        <li>В списке промо-акций найдите нужную промо-акцию</li>
                                        <li>Нажмите кнопку <strong class="text-foreground">"Редактировать"</strong></li>
                                        <li>Внесите необходимые изменения</li>
                                        <li>Нажмите <strong class="text-foreground">"Сохранить"</strong></li>
                                    </ol>
                                </div>

                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Удаление промо-акции</h3>
                                    <ol class="list-decimal list-inside space-y-2 text-muted-foreground">
                                        <li>В списке промо-акций найдите нужную промо-акцию</li>
                                        <li>Нажмите кнопку <strong class="text-foreground">"Удалить"</strong></li>
                                        <li>Подтвердите удаление в диалоговом окне</li>
                                    </ol>
                                </div>

                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Связи</h3>
                                    <ul class="list-disc list-inside space-y-2 text-muted-foreground">
                                        <li><strong class="text-foreground">Промо-акция → Товары</strong> - одна промо-акция может включать множество товаров (связь many-to-many через таблицу product_promotions)</li>
                                    </ul>
                                </div>

                                <div>
                                    <h3 class="text-xl font-semibold mb-3">Примеры</h3>
                                    <div class="bg-muted/30 rounded-lg p-4 mt-3">
                                        <p class="text-sm font-mono text-foreground mb-2">Пример создания промо-акции:</p>
                                        <div class="text-xs text-muted-foreground space-y-1">
                                            <p><strong class="text-foreground">Название:</strong> Новогодняя распродажа</p>
                                            <p><strong class="text-foreground">Описание:</strong> Скидки до 50% на все товары категории "Косметика"</p>
                                            <p><strong class="text-foreground">Дата начала:</strong> 01.12.2024</p>
                                            <p><strong class="text-foreground">Дата окончания:</strong> 31.12.2024</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue';

export default {
    name: 'Documentation',
    setup() {
        const activeSection = ref('categories');
        
        const sections = [
            { id: 'categories', title: 'Категории' },
            { id: 'subcategories', title: 'Подкатегории' },
            { id: 'products', title: 'Все товары' },
            { id: 'promotions', title: 'Промо-акции' },
        ];

        const scrollToSection = (sectionId) => {
            activeSection.value = sectionId;
            const element = document.getElementById(sectionId);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        };

        const handleScroll = () => {
            const scrollPosition = window.scrollY + 200;
            
            for (let i = sections.length - 1; i >= 0; i--) {
                const section = document.getElementById(sections[i].id);
                if (section && section.offsetTop <= scrollPosition) {
                    activeSection.value = sections[i].id;
                    break;
                }
            }
        };

        onMounted(() => {
            window.addEventListener('scroll', handleScroll);
            handleScroll(); // Проверяем начальную позицию
        });

        onUnmounted(() => {
            window.removeEventListener('scroll', handleScroll);
        });

        return {
            sections,
            activeSection,
            scrollToSection,
        };
    },
};
</script>

<style scoped>
.documentation-page {
    min-height: 100vh;
}

.prose {
    color: inherit;
}

.prose h3 {
    color: inherit;
    margin-top: 0;
}

.prose ul,
.prose ol {
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
}

.prose pre {
    background: rgba(0, 0, 0, 0.2);
    padding: 1rem;
    border-radius: 0.5rem;
    overflow-x: auto;
}

.scroll-mt-4 {
    scroll-margin-top: 1rem;
}
</style>

