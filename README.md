# PHP-AutoGpt

PHP-AutoGpt is a simple class implementation designed to showcase capabilities of LLMs in tasks creation and automation. The class generates initial tasks based on the objective, executes those tasks, generates new tasks based on the results, and reprioritizes tasks based on the most recent task completed.

## Usage

First, you will need an API key. You can obtain one from your OpenAI. Once you have your key, include it when constructing the class.

```php
$autogpt = new Pport\AutoGpt($objective, $apiKey);
$autogpt->executeTasks();
```

The `$objective` parameter is a string that defines the end goal of the task. The `$apiKey` parameter is the key provided by the GTP-3 provider.

The executeTasks() method will loop through each task until the objective is reached. It will automatically generate new tasks based on the results of the previous task and prioritize the tasks accordingly.

## Methods

### \_\_construct($objective, $apiKey)

Constructs the AutoGpt class with the given $objective and $apiKey.

### executeTasks()

Executes the list of tasks until the objective is reached.

### generateObjectiveTasks($objective)

Generates the initial list of tasks based on the given objective.

### executeTask($taskDescription)

Executes the given task.

### createNewTasks($result)

Generates new tasks based on the result of the previous task.

### prioritizeTasks($currentTaskName)

Prioritizes the list of tasks based on the given current task.

### sendRequest($prompt)

Sends a request to the GPT-3 API with the given prompt.|
