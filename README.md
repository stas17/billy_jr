Знакомьтесь, Билли Младший. Маленький хранитель денег.

Это микросервис построенный на Symfony и его компонентах.
В качестве брокера очередей rabbitmq, работа с ним происходит через вот это бандл https://github.com/php-amqplib/RabbitMqBundle.
Для хранения средств используется postgresql, взаимодействие с ним через doctrine.
За всем этим наблюдает и пишет логи monolog.
Событие генирируем через отправку сообщения в rebbit.

В качестве воркеров которые разгребают очередь используются Consumer`ы. Для каждой операции отдельный. Можно запускать необходимое количество конкретныйх консьюмеров. За бесперебойной работой которых может следить supervisor.

Вот вкратце, на чем построен сервис и с кем взаимодействует.

Что же умеет Билли? У него есть шесть операций: списание, зачисление, удержание, подтверждение удержания, отмена удержания и трансфер между пользователями.

Для контроля за двойными списаниями или начислениями или вообще повторными операциями используется присылаемый из внешней системы order_id, совместно с типом операции он формирует уникальный ключ.

Развертка приложения. 
По оканчании деплоя supervisor завершает воркеры и начинает запускать новые, нацеливая уже на новый код. В крайнем случае процессы могут быть завершены и принудительно, задачи вернутся в очередь, изменения базы откатятся.

Вот как то так.
Оставил кое какие подсказачки в коде... 
И готов пообщаться и обсудить код, если потребуется.
