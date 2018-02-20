<?php
/**
 * shelob Project ${PROJECT_URL}
 *
 * @link      ${GITHUB_URL} Source code
 */

namespace Sta\Commons;

interface ComparableObject
{
    /**
     * Returns a string representation of the object. In general, the toString method returns a string that "textually
     * represents" this object. The result should be a concise but informative representation that is easy for a person
     * to read. It is recommended that all subclasses override this method.
     *
     * @return string
     *      a string representation of the object.
     */
    public function __toString();

    /**
     * Returns a hash code value for the object. This method is supported for the benefit of array operations aware that
     * their items are a {@link ComparableObject}.
     *
     * The general contract of hashCode is:
     *  . Whenever it is invoked on the same object more than once during an execution of a PHP application, the
     *     hashCode method must consistently return the same integer, provided no information used in equals comparisons
     *     on the object is modified. This integer need not remain consistent from one execution of an application to
     *     another execution of the same application.
     *  . If two objects are equal according to the equals(Object) method, then calling the hashCode method on each of
     *     the two objects must produce the same integer result.
     *  . It is not required that if two objects are unequal according to the equals(java.lang.Object) method, then
     *     calling the hashCode method on each of the two objects must produce distinct integer results. However, the
     *     programmer should be aware that producing distinct integer results for unequal objects may improve the
     *     performance of array operations that are aware their items are a {@link ComparableObject}.
     *
     * @return string
     *      a hash code value for this object.
     */
    public function hashCode(): string;

    /**
     * Indicates whether some other object is "equal to" this one.
     *
     * The equals method implements an equivalence relation on non-null object references:
     *  . It is reflexive: for any non-null reference value x, x.equals(x) should return true.
     *  . It is symmetric: for any non-null reference values x and y, x.equals(y) should return true if and only if
     *      y.equals(x) returns true.
     *  . It is transitive: for any non-null reference values x, y, and z, if x.equals(y) returns true and y.equals(z)
     *      returns true, then x.equals(z) should return true.
     *  . It is consistent: for any non-null reference values x and y, multiple invocations of x.equals(y) consistently
     *      return true or consistently return false, provided no information used in equals comparisons on the objects
     *      is modified.
     *  . For any non-null reference value x, x.equals(null) should return false.
     *
     * Note that it is generally necessary to override the hashCode method whenever this method is overridden, so as to
     * maintain the general contract for the hashCode method, which states that equal objects must have equal hash
     * codes.
     *
     * @param \Sta\Commons\ComparableObject $other
     *      The reference object with which to compare.
     *
     * @return bool
     *      true if this object is the same as the $other argument; false otherwise.
     */
    public function equals(?ComparableObject $other): bool;
}
