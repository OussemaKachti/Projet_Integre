document.addEventListener("DOMContentLoaded", function () {
    loadComments(); // Charger les commentaires au chargement de la page
});

function loadComments() {
    document.querySelectorAll("[id^='comment-list-']").forEach(commentList => {
        let sondageId = commentList.id.split("-").pop();
        
        fetch(`/comment/list/${sondageId}`)
            .then(response => response.json())
            .then(comments => {
                commentList.innerHTML = ""; // Vider la liste actuelle
                comments.forEach(comment => {
                    commentList.innerHTML += generateCommentHTML(comment, sondageId);
                });
            });
    });
}

function generateCommentHTML(comment, sondageId) {
    return `
        <li id="comment-${comment.id}">
            <div class="comments-box grey-bg-2">
                <div class="comments-info d-flex">
                    <div class="comments-avatar mr-20">
                        <img src="/front_assets/img/blog/comments/user.png" alt="">
                    </div>
                    <div class="avatar-name">
                        <h5>${comment.user}</h5>
                        <span class="post-meta">${comment.date}</span>
                    </div>
                    <div class="comments-replay">
                        <a href="#" onclick="editComment(${comment.id}, ${sondageId})">Edit</a> |
                        <a href="#" onclick="deleteComment(${comment.id}, ${sondageId})">Delete</a>
                    </div>
                </div>
                <div class="comments-text ml-65" id="content-${comment.id}">
                    <p>${comment.content}</p>
                </div>
            </div>
        </li>
    `;
}

function addComment(event, sondageId) {
    event.preventDefault();
    let commentText = document.getElementById(`comment-text-${sondageId}`).value;

    if (!commentText.trim()) {
        alert("Le commentaire ne peut pas être vide.");
        return;
    }

    fetch(`/comment/add/${sondageId}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ content: commentText })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
        } else {
            let commentList = document.getElementById(`comment-list-${sondageId}`);
            commentList.innerHTML += generateCommentHTML(data, sondageId);
            document.getElementById(`comment-text-${sondageId}`).value = "";
        }
    });
}

function editComment(commentId, sondageId) {
    let newContent = prompt("Entrez le nouveau commentaire:");
    if (!newContent) return;

    fetch(`/comment/edit/${commentId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ content: newContent })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
        } else {
            document.getElementById(`content-${commentId}`).innerHTML = `<p>${newContent}</p>`;
        }
    });
}

function deleteComment(commentId, sondageId) {
    if (!confirm("Voulez-vous vraiment supprimer ce commentaire ?")) return;

    fetch(`/comment/delete/${commentId}`, { method: 'DELETE' })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
        } else {
            document.getElementById(`comment-${commentId}`).remove();
        }
    });
}
