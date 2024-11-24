<div class="back_to_panel">
    <a href="{{route('admin.admin.back_to_admin_panel')}}">
        <i class="fas fa-sign-out-alt"></i>
        <br>
        <span>{{auth('admin')->user()->fullname()}}</span>
        <br>
        <span>Back to Panel</span>
    </a>
</div>

<style>
    @keyframes pulse {
        0% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(76,175,80,.7);
        }

        70% {
            transform: scale(1.05);
            box-shadow: 0 0 0 10px rgba(76,175,80, 0);
        }

        100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(76,175,80, 0);
        }
    }

    .back_to_panel {
        position: fixed; /* Fixed/sticky position */
        bottom: 20px; /* Place the button at the bottom of the page */
        left: 20px; /* Place the button 20px from the right */
        z-index: 9999; /* Ensure it sits on top of everything else */
    }

    .back_to_panel a {
        background-color: #f62e2e; /* Green */
        color: white;
        padding: 15px 32px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 16px;
        border: none;
        border-radius: 50%; /* Make the button circular */
        cursor: pointer;
        animation: pulse 2s infinite; /* Apply the pulse animation */
    }
</style>
