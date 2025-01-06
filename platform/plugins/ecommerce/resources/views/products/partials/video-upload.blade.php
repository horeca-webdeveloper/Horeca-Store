<!--{{-- <div>-->
<!--    <label for="videos">Upload Videos</label>-->
<!--    <input type="file" id="videos" name="videos[]" accept="video/*" multiple>-->

<!--    @if(!empty($videos) && count($videos) > 0)-->
<!--        <div class="uploaded-videos mt-3">-->
<!--            <h4>Uploaded Videos</h4>-->
<!--            <ul>-->
<!--                @foreach($videos as $video)-->
<!--                    <li>-->
<!--                        <video width="320" height="240" controls>-->
<!--                            <source src="{{ asset('storage/' . $video) }}" type="video/mp4">-->
<!--                        </video>-->
<!--                        <button type="button" class="delete-video btn btn-danger" data-video="{{ $video }}">Delete</button>-->
<!--                    </li>-->
<!--                @endforeach-->
<!--            </ul>-->
<!--        </div>-->
<!--    @endif-->
<!--</div> --}}-->

<!--{{-- -->
<!--<div>-->
<!--    <label for="videos">Upload Videos</label>-->
<!--    <input type="file" id="videos" name="videos[]" accept="video/*" multiple>-->

    <!-- Hidden field to store deleted video paths -->
<!--    <input type="hidden" name="deleted_videos" id="deleted_videos" value="">-->

<!--    @if(!empty($videos) && count($videos) > 0)-->
<!--        <div class="uploaded-videos mt-3">-->
<!--            <h4>Uploaded Videos</h4>-->
<!--            <ul>-->
<!--                @foreach($videos as $video)-->
<!--                    <li>-->
<!--                        <video width="320" height="240" controls>-->
<!--                            <source src="{{ asset('storage/' . $video) }}" type="video/mp4">-->
<!--                        </video>-->
<!--                        <button type="button" class="delete-video btn btn-danger" data-video="{{ $video }}">Delete</button>-->
<!--                    </li>-->
<!--                @endforeach-->
<!--            </ul>-->
<!--        </div>-->
<!--    @endif-->
<!--</div>-->

<!--<script>-->
    // Handle the delete button click event
<!--    document.querySelectorAll('.delete-video').forEach(function(button) {-->
<!--        button.addEventListener('click', function() {-->
            var videoPath = this.getAttribute('data-video'); // Get the video path from the data attribute
            var deletedVideosInput = document.getElementById('deleted_videos'); // Get the hidden input
            var deletedVideos = deletedVideosInput.value ? deletedVideosInput.value.split(',') : []; // Split existing values into an array

            // Check if the video is already in the array, if not, add it
<!--            if (!deletedVideos.includes(videoPath)) {-->
<!--                deletedVideos.push(videoPath);-->
                deletedVideosInput.value = deletedVideos.join(','); // Update the hidden input
<!--            }-->

            // Remove the video element from the DOM
            this.closest('li').remove(); // Remove the parent <li> element
<!--        });-->
<!--    });-->
<!--</script> --}}-->


<div>
    <label for="videos">Upload Videos</label>
    <input type="file" id="videos" name="videos[]" accept="video/*" multiple>

    <!-- Hidden field to store deleted video paths -->
    <input type="hidden" name="deleted_videos" id="deleted_videos" value="">

    @if(!empty($videos) && count($videos) > 0)
        <div class="uploaded-videos mt-3">
            <h4>Uploaded Videos</h4>
            <ul>
                @foreach($videos as $video)
                    <li>
                        <video width="320" height="240" controls>
                            <source src="{{ asset('storage/' . $video) }}" type="video/mp4">
                        </video>
                        <button type="button" class="delete-video btn btn-danger" data-video="{{ $video }}">Delete</button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div id="upload-progress" class="mt-3"></div> <!-- Container for upload progress bars -->
</div>

<script>
    // Handle file input change event
    document.getElementById('videos').addEventListener('change', function(event) {
        const files = event.target.files; // Get selected files
        const uploadProgressContainer = document.getElementById('upload-progress'); // Progress container

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const formData = new FormData();
            formData.append('videos[]', file); // Append the video file to FormData

            // Create a new XMLHttpRequest
            const xhr = new XMLHttpRequest();

            // Update progress bar
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100; // Calculate percentage
                    const progressHtml = `
                        <div class="progress mb-2">
                            <div class="progress-bar" role="progressbar" style="width: ${percentComplete}%" aria-valuenow="${percentComplete}" aria-valuemin="0" aria-valuemax="100">${Math.round(percentComplete)}%</div>
                        </div>
                        <p>${file.name} - ${Math.round(percentComplete)}% uploaded</p>
                    `;
                    uploadProgressContainer.innerHTML += progressHtml; // Append progress bar to the container
                }
            });

            // Handle the response from the server
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    console.log('Upload complete: ', xhr.responseText);
                    // Optionally, you can refresh the video list here
                }
            };

            // Send the FormData with the video file
            xhr.open('POST', '/your-upload-route', true); // Replace with your actual upload route
            xhr.send(formData);
        }
    });

    // Handle the delete button click event
    document.querySelectorAll('.delete-video').forEach(function(button) {
        button.addEventListener('click', function() {
            var videoPath = this.getAttribute('data-video'); // Get the video path from the data attribute
            var deletedVideosInput = document.getElementById('deleted_videos'); // Get the hidden input
            var deletedVideos = deletedVideosInput.value ? deletedVideosInput.value.split(',') : []; // Split existing values into an array

            // Check if the video is already in the array, if not, add it
            if (!deletedVideos.includes(videoPath)) {
                deletedVideos.push(videoPath);
                deletedVideosInput.value = deletedVideos.join(','); // Update the hidden input
            }

            // Remove the video element from the DOM
            this.closest('li').remove(); // Remove the parent <li> element
        });
    });
</script>
