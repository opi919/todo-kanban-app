<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks Board</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen p-6">
    @php
        function statusColor($status)
        {
            return match ($status) {
                'pending' => 'bg-yellow-50 text-yellow-800 border-yellow-400',
                'in_progress' => 'bg-blue-100 text-blue-800 border-blue-400',
                'completed' => 'bg-green-100 text-green-800 border-green-400',
                default => 'bg-gray-100 text-gray-800 border-gray-300',
            };
        }

        function priorityColor($priority)
        {
            return match (strtolower($priority)) {
                'low' => 'text-green-600 bg-green-100 px-2 py-0.5 rounded',
                'medium' => 'text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded',
                'high' => 'text-orange-700 bg-orange-100 px-2 py-0.5 rounded',
                'urgent' => 'text-red-700 bg-red-100 px-2 py-0.5 rounded',
                default => 'text-gray-700 bg-gray-100 px-2 py-0.5 rounded',
            };
        }
    @endphp

    <div class="container mx-auto">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-8">Task Board</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($tasks as $status => $tasksGroup)
                <div class="bg-white rounded-xl shadow-lg p-5">
                    <h2 class="text-xl font-semibold mb-4 border-b pb-2 {{ statusColor($status) }} p-2 rounded">
                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                    </h2>

                    @forelse ($tasksGroup as $task)
                        <div class="bg-white border-l-4 {{ statusColor($status) }} rounded-md p-4 mb-4 shadow-sm hover:shadow-md transition"
                            style="background-color: white;">
                            <h3 class="text-lg font-bold text-gray-800">{{ $task->title }}</h3>
                            <p class="text-sm text-gray-600 mt-1">
                                Priority:
                                <span class="font-semibold {{ priorityColor($task->priority) }}">
                                    {{ ucfirst($task->priority) }}
                                </span>
                            </p>
                            <p class="text-sm text-gray-600">Assigned To: <span
                                    class="font-medium">{{ $task->assignedUser->name }}</span></p>
                            <p class="text-sm text-gray-600">Due: <span
                                    class="font-medium">{{ $task->due_date ?? 'N/A' }}</span></p>
                        </div>
                    @empty
                        <p class="text-gray-500 italic">No tasks in this status.</p>
                    @endforelse
                </div>
            @endforeach
        </div>
    </div>
</body>

</html>
