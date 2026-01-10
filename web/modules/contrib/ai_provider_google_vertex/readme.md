# Google Vertex Provider

The Google Vertex provider is an AI provider for the AI module that lets you use the chat and embeddings models from the Model Garden. The Gemini model has unique capabilities in document and video inputs.

## Post Installation
1. Install the Google Vertex module.
2. Create an account on Google Cloud.
3. In Google Cloud Console enable the Vertex API.
4. Create a credential file with access to the API.
5. Add the location of this file into a key entity at /admin/config/system/keys.
6. The visit Google Provider settings on your Drupal website under /admin/config/ai/providers/google_vertex.
7. Click Add and the type of model to add.
8. Type in region, model and project id
9. Click Create Model.
10. You are now ready to use it.

## Getting streaming working
Follow the following instructions: https://cloud.google.com/php/grpc

Note that your webserver also have to allow to send out chunks.
