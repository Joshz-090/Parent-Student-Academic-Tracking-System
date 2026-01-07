/**
 * Tracer Timeline Logic - AJAX Edition
 */

async function fetchTracerTimeline(studentId, filter = 'all') {
    const timelineContainer = document.getElementById('tracer-timeline');
    if (!timelineContainer) return;

    // Show loading state
    timelineContainer.innerHTML = '<div class="text-center py-20"><p class="text-gray-400 font-bold animate-pulse">Tracing history...</p></div>';

    try {
        const response = await fetch(`/api/tracer?student_id=${studentId}&filter=${filter}`);
        const data = await response.json();

        if (data.success) {
            renderTimeline(data.activities);
        } else {
            timelineContainer.innerHTML = `<div class="bg-red-50 p-8 rounded-3xl text-red-800">${data.message}</div>`;
        }
    } catch (error) {
        console.error('Error fetching tracer:', error);
    }
}

function renderTimeline(activities) {
    const timelineContainer = document.getElementById('tracer-timeline');
    if (activities.length === 0) {
        timelineContainer.innerHTML = '<p class="text-center py-20 text-gray-400 italic">No events recorded yet.</p>';
        return;
    }

    const icons = {
        'x-circle': '✕',
        'clock': '🕒',
        'check-circle': '✓',
        'star': '⭐',
        'trending-up': '📈',
        'message-square': '💬'
    };

    const bentoHtml = activities.map(activity => `
        <div class="bg-white p-6 rounded-[32px] border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 group">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl shadow-lg shadow-${activity.color}-200 bg-${activity.color}-50 text-${activity.color}-600 group-hover:scale-110 transition-transform">
                    ${icons[activity.icon] || '•'}
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-black text-gray-900">${activity.title}</h4>
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">${activity.formatted_date}</span>
                    </div>
                    <p class="text-sm text-gray-500 font-medium leading-relaxed">${activity.description || ''}</p>
                    <div class="mt-4">
                        <span class="inline-block px-3 py-1 bg-gray-50 text-[10px] font-black text-gray-400 rounded-full uppercase tracking-tighter">
                            ${activity.activity_type}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    `).join('');

    timelineContainer.innerHTML = `<div class="grid grid-cols-1 md:grid-cols-2 gap-6">${bentoHtml}</div>`;
}
