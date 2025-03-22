<?php
/**
 * Constantes do sistema 
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 15:47:22
 */

// Prevenir acesso direto ao arquivo
defined('BASE_PATH') or exit('No direct script access allowed');

// Status de usuário
define('STATUS_ACTIVE', 'active');
define('STATUS_BLOCKED', 'blocked');
define('STATUS_DELETED', 'deleted');
define('STATUS_PENDING', 'pending');

// Gêneros
define('GENDER_MALE', 'M');
define('GENDER_FEMALE', 'F');
define('GENDER_OTHER', 'O');

// Preferências
define('INTERESTED_IN_MALE', 'M');
define('INTERESTED_IN_FEMALE', 'F');
define('INTERESTED_IN_BOTH', 'B');

// Status de match
define('MATCH_PENDING', 'pending');
define('MATCH_MATCHED', 'matched');
define('MATCH_REJECTED', 'rejected');
define('MATCH_UNMATCHED', 'unmatched');

// Tipos de notificação
define('NOTIFICATION_MATCH', 'match');
define('NOTIFICATION_MESSAGE', 'message');
define('NOTIFICATION_LIKE', 'like');
define('NOTIFICATION_VISIT', 'visit');

// Tipos de relatório
define('REPORT_FAKE', 'fake');
define('REPORT_INAPPROPRIATE', 'inappropriate');
define('REPORT_HARASSMENT', 'harassment');
define('REPORT_SPAM', 'spam');
define('REPORT_OTHER', 'other');

// Status de relatório
define('REPORT_PENDING', 'pending');
define('REPORT_REVIEWING', 'reviewing');
define('REPORT_RESOLVED', 'resolved');

// Papéis de administrador
define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_ADMIN', 'admin');
define('ROLE_MODERATOR', 'moderator');

// Tipos de arquivo
define('FILE_TYPE_IMAGE', ['jpg', 'jpeg', 'png', 'gif']);
define('FILE_TYPE_DOCUMENT', ['pdf', 'doc', 'docx']);

// Tipos de ação
define('ACTION_LOGIN', 'login');
define('ACTION_LOGOUT', 'logout');
define('ACTION_REGISTER', 'register');
define('ACTION_UPDATE', 'update');
define('ACTION_DELETE', 'delete');
define('ACTION_BLOCK', 'block');
define('ACTION_UNBLOCK', 'unblock');
define('ACTION_MATCH', 'match');
define('ACTION_UNMATCH', 'unmatch');
define('ACTION_MESSAGE', 'message');
define('ACTION_REPORT', 'report');

// Status de mensagem
define('MESSAGE_SENT', 'sent');
define('MESSAGE_DELIVERED', 'delivered');
define('MESSAGE_READ', 'read');
define('MESSAGE_DELETED', 'deleted');

// Tipos de busca
define('SEARCH_NEARBY', 'nearby');
define('SEARCH_NEW', 'new');
define('SEARCH_POPULAR', 'popular');
define('SEARCH_ONLINE', 'online');

// Ordenação
define('ORDER_NEWEST', 'newest');
define('ORDER_OLDEST', 'oldest');
define('ORDER_DISTANCE', 'distance');
define('ORDER_RELEVANCE', 'relevance');

// Filtros
define('FILTER_AGE', 'age');
define('FILTER_DISTANCE', 'distance');
define('FILTER_GENDER', 'gender');
define('FILTER_ONLINE', 'online');
define('FILTER_PHOTO', 'photo');

// Limites
define('LIMIT_MATCHES', 100);
define('LIMIT_MESSAGES', 1000);
define('LIMIT_NOTIFICATIONS', 100);
define('LIMIT_REPORTS', 50);
define('LIMIT_PHOTOS', 6);
define('LIMIT_BLOCKS', 100);

// Intervalos de tempo
define('INTERVAL_ONLINE', 300); // 5 minutos
define('INTERVAL_INACTIVE', 1800); // 30 minutos
define('INTERVAL_OFFLINE', 86400); // 24 horas

// HTTP Status
define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_ACCEPTED', 202);
define('HTTP_NO_CONTENT', 204);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_METHOD_NOT_ALLOWED', 405);
define('HTTP_CONFLICT', 409);
define('HTTP_INTERNAL_ERROR', 500);

// Mensagens de erro
define('ERROR_LOGIN', 'Usuário ou senha inválidos');
define('ERROR_REGISTER', 'Erro ao registrar usuário');
define('ERROR_UPDATE', 'Erro ao atualizar dados');
define('ERROR_DELETE', 'Erro ao excluir registro');
define('ERROR_NOT_FOUND', 'Registro não encontrado');
define('ERROR_PERMISSION', 'Permissão negada');
define('ERROR_VALIDATION', 'Dados inválidos');
define('ERROR_UPLOAD', 'Erro no upload do arquivo');
define('ERROR_DATABASE', 'Erro no banco de dados');

// Mensagens de sucesso
define('SUCCESS_LOGIN', 'Login realizado com sucesso');
define('SUCCESS_REGISTER', 'Registro realizado com sucesso');
define('SUCCESS_UPDATE', 'Dados atualizados com sucesso');
define('SUCCESS_DELETE', 'Registro excluído com sucesso');
define('SUCCESS_UPLOAD', 'Arquivo enviado com sucesso');
define('SUCCESS_MESSAGE', 'Mensagem enviada com sucesso');
define('SUCCESS_MATCH', 'Match realizado com sucesso');

// Permissões
define('PERMISSION_READ', 1);
define('PERMISSION_WRITE', 2);
define('PERMISSION_UPDATE', 4);
define('PERMISSION_DELETE', 8);
define('PERMISSION_ADMIN', 16);