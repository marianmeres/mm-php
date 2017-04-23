<?php
/**
 * @author Marian Meres
 */
namespace MM\Session;

/**
 * Mimick php 5.4+
 * http://php.net/manual/en/class.sessionhandlerinterface.php
 */
interface SaveHandlerInterface
{
    /**
     * Re-initialize existing session, or creates a new one. Called when
     * a session starts or when session_start() is invoked.
     *
     * @param $savePath
     * @param $sessName
     * @return boolean
     */
    public function open($savePath, $sessName);

    /**
     * Reads the session data from the session storage, and returns the
     * results. Called right after the session starts or when session_start()
     * is called. Please note that before this method is called
     * SessionHandlerInterface::open() is invoked.
     *
     * This method is called by PHP itself when the session is started. This
     * method should retrieve the session data from storage by the session ID
     * provided. The string returned by this method must be in the same
     * serialized format as when originally passed to the
     * SessionHandlerInterface::write() If the record was not found, return
     * an empty string.
     *
     * The data returned by this method will be decoded internally by PHP using
     * the unserialization method specified in session.serialize_handler. The
     * resultig data will be used to populate the $_SESSION superglobal.
     *
     * Note that the serialization scheme is not the same as unserialize() and
     * can be accessed by session_decode().
     *
     * @param  string $sessId
     * @return string         Returns an encoded string of the read data.
     *                        If nothing was read, it must return an empty
     *                        string.
     */
    public function read($sessId);

    /**
     * Writes the session data to the session storage. Called by
     * session_write_close(), when session_register_shutdown() fails, or
     * during a normal shutdown. Note: SessionHandlerInterface::close() is
     * called immediately after this function.
     *
     * PHP will call this method when the session is ready to be saved and
     * closed. It encodes the session data from the $_SESSION superglobal to
     * a serialized string and passes this along with the session ID to this
     * method for storage. The serialization method used is specified in the
     * session.serialize_handler setting.
     *
     * Note this method is normally called by PHP after the output buffers
     * have been closed unless explicitly called by session_write_close()
     *
     * @param  string $sessId
     * @param  string $data   The encoded session data. This data is the result
     *                        of the PHP internally encoding the $_SESSION
     *                        superglobal to a serialized string and passing
     *                        it as this parameter. Please note sessions use an
     *                        alternative serialization method.
     * @return boolean
     */
    public function write($sessId, $data);

    /**
     * Closes the current session. This function is automatically executed when
     * closing the session, or explicitly via session_write_close().
     *
     * @return boolean
     */
    public function close();

    /**
     * Destroys a session. Called by session_regenerate_id()
     * (with $destroy = TRUE), session_destroy() and when session_decode() fails.
     *
     * @param  string $sessId
     * @return boolean
     */
    public function destroy($sessId);

    /**
     * Cleans up expired sessions. Called by session_start(), based on
     * ession.gc_divisor, session.gc_probability and session.gc_lifetime
     * settings.
     *
     * @param int $maxlifetime "Sessions that have not updated for the last
     *                           maxlifetime seconds will be removed."
     * @return mixed
     */
    public function gc($maxlifetime);

    /**
     * Sugar. Shutdown ako parameter, kvoli mockovaniu/wrapu
     *
     * @param bool $registerShutdown
     * @return mixed
     */
    public function registerSaveHandler($registerShutdown = true);

    /**
     * @param $ttlSeconds
     * @return mixed
     */
    public function setLifetime($ttlSeconds);

    /**
     * @return mixed
     */
    public function getLifetime();
}

