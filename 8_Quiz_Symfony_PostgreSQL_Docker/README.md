# Quiz (testing) system that supports questions with fuzzy logic and the ability to choose multiple answer options.

## Task description

You can check [task description here](docs/task-description.md)

## Setup instructions

Docker setup based on [oficiialy recommended](https://symfony.com/doc/current/setup/docker.html) [symfony-docker - A Docker-based installer and runtime for the Symfony web framework](https://github.com/dunglas/symfony-docker)

If not already done, [install Docker Compose](https://docs.docker.com/compose/install/). My current version locally:

```
docker compose version
Docker Compose version v2.26.1-desktop.1
```

And run Docker Desktop. My current version: `v4.29.0`

Clone this repository and go to `quiz` project directory

```
git clone https://github.com/ruslanix/code-samples.git
cd code-samples/8_Quiz_Symfony_PostgreSQL_Docker
```

Build images
```
docker compose build --no-cache --pull
```

Start project and wait couple of seconds
```
docker compose up -d
```

Initialize database. Below will run commands in `php` docker container
```
docker compose run php bin/console doctrine:database:create --if-not-exists
docker compose run php bin/console doctrine:migrations:migrate --no-interaction
```

Open `https://localhost` in your browser and accept the auto-generated TLS certificate.

By default project run in `dev` mode.

To stop project
```
docker compose down --remove-orphans
```

Or to stop and remove volumes
```
docker compose down --remove-orphans -v
```

## Implementation notes

In real life there will be long session about how this system is going to evolve, what are they main business use cases now and in future.  But now this is a test task and I deliberately don't produce much interfaces or abstractions. They will pollute code. Mostly I just use concrete classes and services. Quiz itself and results saved in DB as `json` columns.

Different moments:
- `App\Controller\QuizController`- entry point and main controller
- `App\Model\Quiz` - quiz configuration with questions and answers
- `App\Model\Passage` - is basically quiz passage. Contains quiz itself, answers and all what needed to run passage session
- `App\Services` - services used to load Quiz, run Passage, evaluate answers and save results in DB
- `App\Exception\QuizException\QuizExceptionListener` - process quiz module exceptions and shows custom error page
- `App\Serializer` - serializer (de)normalizers to process Quiz models relation references
- Current (in progress) passage session saved in Session
- Results saved in DB with  `App\Entity\PassageResults`
- @TODO: write tests ...
